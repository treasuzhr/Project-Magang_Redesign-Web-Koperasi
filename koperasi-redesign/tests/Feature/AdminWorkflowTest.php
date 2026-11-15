<?php

namespace Tests\Feature;

use App\AdminLoans;
use App\AdminOrders;
use App\Filament\Resources\Beritas\Pages\CreateBerita;
use App\Filament\Resources\Beritas\Pages\EditBerita;
use App\Filament\Resources\Dokumens\Pages\CreateDokumen;
use App\Filament\Resources\Dokumens\Pages\ListDokumens;
use App\Filament\Resources\SimpananTransaksis\Pages\ListSimpananTransaksis;
use App\MemberOverview;
use App\Models\Berita;
use App\Models\Dokumen;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_to_member_workflow_for_the_five_modules(): void
    {
        $this->withoutVite();
        $this->seed(CooperativeDemoSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        // Simulate accounts that have completed the first-password-change step, only in this test.
        User::whereIn('nip', [CooperativeDemoSeeder::ADMIN_NIP, CooperativeDemoSeeder::ANGGOTA_NIP])->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', ['member_id' => CooperativeDemoSeeder::ADMIN_NIP, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $member = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail()->anggota;
        $before = app(MemberOverview::class)->savings($member)['summary']['sukarela'];
        $input = ['anggota_id' => $member->id, 'jenis' => 'sukarela', 'tanggal' => today()->toDateString(), 'keterangan' => 'Pengujian alur'];
        Livewire::test(ListSimpananTransaksis::class)->callAction('record', $input + ['tipe' => 'setor', 'nominal' => 50000])->assertHasNoActionErrors();
        Livewire::test(ListSimpananTransaksis::class)->callAction('record', $input + ['tipe' => 'tarik', 'nominal' => 10000])->assertHasNoActionErrors();
        Livewire::test(ListSimpananTransaksis::class)->callAction('record', $input + ['tipe' => 'tarik', 'nominal' => 999999999])->assertHasActionErrors(['nominal']);
        $csv = 'nip,jenis,jumlah,tanggal,keterangan'."\n".CooperativeDemoSeeder::ANGGOTA_NIP.',wajib,1234,2026-01-19,Uji impor';
        foreach ([1, 0] as $created) {
            $component = Livewire::test(ListSimpananTransaksis::class)->callAction('importSavings', ['csv' => UploadedFile::fake()->createWithContent('setoran.csv', $csv)])->assertHasNoActionErrors();
            $this->assertSame($created, $component->get('importResult')['created']);
        }
        $news = ['judul' => 'Verifikasi admin', 'slug' => 'verifikasi-admin', 'ringkasan' => 'Ringkasan verifikasi.', 'isi' => '<p>Isi untuk pengujian alur.</p>', 'is_published' => false, 'published_at' => now()->subMinute()->format('Y-m-d H:i:s')];
        Livewire::test(CreateBerita::class)->fillForm($news)->call('create')->assertHasNoFormErrors();
        $article = Berita::where('slug', 'verifikasi-admin')->firstOrFail();
        $this->get('/berita/verifikasi-admin')->assertNotFound();
        Livewire::test(EditBerita::class, ['record' => $article->id])->fillForm(['is_published' => true, 'published_at' => now()->subMinute()->format('Y-m-d H:i:s')])->call('save')->assertHasNoFormErrors();
        $this->get('/berita/verifikasi-admin')->assertOk()->assertSee('Isi untuk pengujian alur.');
        $file = UploadedFile::fake()->createWithContent('verifikasi.pdf', "%PDF-1.4\n%%EOF");
        Livewire::test(CreateDokumen::class)->fillForm(['judul' => 'Dokumen verifikasi', 'tahun' => 2026, 'file_path' => $file, 'published' => false])->call('create')->assertHasNoFormErrors();
        $document = Dokumen::where('judul', 'Dokumen verifikasi')->firstOrFail();
        Livewire::test(ListDokumens::class)->set('activeTab', 'draf')->call('updateTableColumnState', 'published', (string) $document->id, true);
        $jenis = PinjamanJenis::where('kode', 'usipa')->firstOrFail();
        $loan = Pinjaman::create(['anggota_id' => $member->id, 'pinjaman_jenis_id' => $jenis->id, 'kode' => 'UJI-ADMIN-CAIR', 'jumlah' => 12000000, 'tenor_bulan' => 12, 'bunga_persen_per_bulan' => $jenis->bunga_persen_per_bulan, 'metode_bayar' => 'potong_gaji', 'status' => 'diajukan', 'tanggal_pengajuan' => today()]);
        app(AdminLoans::class)->approve($loan);
        app(AdminLoans::class)->disburse($loan);
        $this->assertSame(today()->toDateString(), $loan->tanggal_pencairan->toDateString());
        $order = $member->pesanans()->where('status', 'siap')->firstOrFail();
        app(AdminOrders::class)->transition($order, 'selesai');
        $this->assertNotNull($order->selesai_pada);
        $this->post('/logout')->assertRedirect('/');
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', ['member_id' => CooperativeDemoSeeder::ANGGOTA_NIP, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE'])->assertRedirect('/anggota');
        $this->get('/anggota/simpanan/sukarela')->assertOk()->assertViewHas('summary', fn (array $summary): bool => $summary['sukarela'] === $before + 40000);
        $this->get('/anggota/dokumen')->assertOk()->assertSee('Dokumen verifikasi');
        $this->get(route('anggota.dokumen.download', $document->id))->assertDownload(basename($document->file_path));
        $this->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee('Dicairkan pada');
        $this->get('/anggota/pesanan/'.$order->id)->assertOk()->assertSee('Selesai pada');
        $this->get('/admin')->assertForbidden();
    }
}
