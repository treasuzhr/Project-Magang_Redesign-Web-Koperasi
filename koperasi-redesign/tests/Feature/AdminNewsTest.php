<?php

namespace Tests\Feature;

use App\AdminNews;
use App\Filament\Resources\Beritas\Pages\CreateBerita;
use App\Filament\Resources\Beritas\Pages\EditBerita;
use App\Filament\Resources\Beritas\Pages\ListBeritas;
use App\Models\Anggota;
use App\Models\Berita;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function input(array $extra = []): array
    {
        return $extra + ['judul' => 'Berita pengujian', 'slug' => 'berita-pengujian', 'ringkasan' => 'Ringkasan pengujian.', 'isi' => '<h2>Pengumuman</h2><p>Isi pengujian.</p>', 'is_published' => false, 'published_at' => now()->subDay()->format('Y-m-d H:i:s')];
    }

    public function test_crud_and_unique_slug_using_existing_columns(): void
    {
        Storage::fake('public');
        Livewire::test(CreateBerita::class)->fillForm($this->input(['foto' => UploadedFile::fake()->image('sampul.jpg')]))->call('create')->assertHasNoFormErrors();
        $record = Berita::firstOrFail();
        $this->assertNull($record->published_at);
        $this->assertTrue(Storage::disk('public')->exists($record->foto));
        $this->get('/berita/'.$record->slug)->assertNotFound();
        Livewire::test(CreateBerita::class)->fillForm($this->input())->call('create')->assertHasFormErrors(['slug' => 'unique']);
        Livewire::test(EditBerita::class, ['record' => $record->id])->fillForm($this->input(['is_published' => true, 'judul' => 'Berita diubah']))->call('save')->assertHasNoFormErrors();
        $this->get('/berita/'.$record->slug)->assertOk()->assertSee('Berita diubah');
        $this->get('/')->assertOk()->assertViewHas('news', fn (array $news): bool => $news[0]['title'] === 'Berita diubah');
        Livewire::test(ListBeritas::class)->assertCanSeeTableRecords([$record->fresh()])->callTableAction('delete', $record)->assertHasNoTableActionErrors();
        $this->assertModelMissing($record);
    }

    public function test_draft_and_scheduled_articles_stay_private_on_all_public_pages(): void
    {
        $draft = app(AdminNews::class)->save($this->input());
        $future = app(AdminNews::class)->save($this->input(['judul' => 'Berita masa depan', 'slug' => 'masa-depan', 'is_published' => true, 'published_at' => now()->addDay()->format('Y-m-d H:i:s')]));
        $this->get('/')->assertOk()->assertDontSee($draft->judul)->assertDontSee($future->judul);
        $this->get('/berita')->assertOk()->assertDontSee($draft->judul)->assertDontSee($future->judul);
        $this->get('/berita/'.$future->slug)->assertNotFound();
        $this->travel(2)->days();
        $this->get('/berita/'.$future->slug)->assertOk();
    }

    public function test_sanitization_is_applied_on_save_and_again_for_untrusted_stored_content(): void
    {
        $malicious = '<h2 onclick="alert(1)">Judul aman</h2><p style="background:url(javascript:alert(2))">Isi aman</p><script>alert(3)</script><a href="javascript:alert(4)" onmouseover="alert(5)">tautan</a><iframe src="https://example.com"></iframe>';
        $record = app(AdminNews::class)->save($this->input(['isi' => $malicious, 'is_published' => true]));
        foreach (['<script', 'onclick', 'javascript:', 'onmouseover', '<iframe', 'style='] as $bad) {
            $this->assertStringNotContainsString($bad, $record->isi['html']);
        }
        $record->update(['isi' => ['html' => $malicious]]);
        $response = $this->get('/berita/'.$record->slug)->assertOk()->assertSee('<h2>Judul aman</h2>', false)->assertSee('Isi aman');
        foreach (['onclick="alert', 'javascript:alert', 'onmouseover="alert', '<script>alert(3)', '<iframe'] as $bad) {
            $response->assertDontSee($bad, false);
        }
    }

    public function test_editing_existing_article_preserves_its_supplied_inline_photo(): void
    {
        $original = config('news.articles.1');
        $record = Berita::create(['judul' => $original['title'], 'slug' => 'artikel-lama', 'ringkasan' => $original['excerpt'], 'isi' => $original['sections'], 'foto' => $original['image'], 'published_at' => now()->subDay()]);
        app(AdminNews::class)->save($this->input(['slug' => 'artikel-lama', 'is_published' => true]), $record);
        $this->assertNotEmpty($record->isi['inline_images']);
        $this->get('/berita/artikel-lama')->assertOk()->assertSee('data-news-inline-image', false);
    }

    public function test_cover_validation_and_member_access(): void
    {
        Storage::fake('public');
        Livewire::test(CreateBerita::class)->fillForm($this->input(['foto' => UploadedFile::fake()->create('cover.pdf', 10, 'application/pdf')]))->call('create')->assertHasFormErrors(['foto']);
        Livewire::test(CreateBerita::class)->fillForm($this->input(['foto' => UploadedFile::fake()->image('large.jpg')->size(2049)]))->call('create')->assertHasFormErrors(['foto']);
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $this->actingAs($member->user)->get('/admin/berita')->assertForbidden();
        $this->get('/admin/berita/create')->assertForbidden();
    }
}
