<?php

namespace Tests\Feature;

use App\AdminDocuments;
use App\Filament\Resources\Dokumens\Pages\CreateDokumen;
use App\Filament\Resources\Dokumens\Pages\EditDokumen;
use App\Filament\Resources\Dokumens\Pages\ListDokumens;
use App\Models\Anggota;
use App\Models\Dokumen;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class AdminDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function pdf(string $name = 'berkas.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
    }

    private function office(string $extension): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'primkop-doc-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
        $zip->addFromString($extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml', '<?xml version="1.0"?><document/>');
        $zip->close();
        $file = UploadedFile::fake()->createWithContent('berkas.'.$extension, file_get_contents($path));
        unlink($path);

        return $file;
    }

    private function create(UploadedFile $file, string $title = 'Dokumen uji'): Dokumen
    {
        Livewire::test(CreateDokumen::class)->fillForm(['judul' => $title, 'tahun' => 2026, 'file_path' => $file, 'published' => false])->call('create')->assertHasNoFormErrors();

        return Dokumen::where('judul', $title)->firstOrFail();
    }

    public function test_upload_accepts_pdf_docx_xlsx_only_on_private_disk(): void
    {
        foreach ([$this->pdf(), $this->office('docx'), $this->office('xlsx')] as $i => $file) {
            $document = $this->create($file, 'Dokumen '.$i);
            $this->assertTrue(Storage::disk('local')->exists($document->file_path));
            $this->assertFalse(Storage::disk('public')->exists($document->file_path));
            $this->assertStringContainsString('documents/', $document->file_path);
            $this->assertNotSame('-', app(AdminDocuments::class)->metadata($document)['type']);
            Livewire::test(EditDokumen::class, ['record' => $document->id])->assertDontSee('/storage/'.$document->file_path, false);
        }
    }

    public function test_invalid_extensions_and_sizes_are_rejected(): void
    {
        foreach ([UploadedFile::fake()->image('bad.jpg'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            Livewire::test(CreateDokumen::class)->fillForm(['judul' => 'Tidak valid', 'tahun' => 2026, 'file_path' => $file, 'published' => false])->call('create')->assertHasFormErrors(['file_path']);
        }
        Storage::disk('local')->put('documents/renamed.docx', 'not a real office document');
        try {
            app(AdminDocuments::class)->save(['judul' => 'Palsu', 'tahun' => 2026, 'file_path' => 'documents/renamed.docx', 'published' => false]);
            $this->fail();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file_path', $exception->errors());
        }
        $this->assertSame(0, Dokumen::count());
    }

    public function test_replace_and_delete_clean_old_files_and_keep_other_documents(): void
    {
        $record = $this->create($this->pdf());
        $old = $record->file_path;
        Livewire::test(EditDokumen::class, ['record' => $record->id])->set('data.file_path', [])->fillForm(['file_path' => $this->pdf('pengganti.pdf')])->call('save')->assertHasNoFormErrors();
        $record->refresh();
        $this->assertNotSame($old, $record->file_path);
        $this->assertFalse(Storage::disk('local')->exists($old));
        $this->assertTrue(Storage::disk('local')->exists($record->file_path));
        $other = $this->create($this->pdf(), 'Dokumen lain');
        Livewire::test(ListDokumens::class)->set('activeTab', 'draf')->callTableAction('delete', $record)->assertHasNoTableActionErrors();
        $this->assertModelMissing($record);
        $this->assertFalse(Storage::disk('local')->exists($record->file_path));
        $this->assertTrue(Storage::disk('local')->exists($other->file_path));
    }

    public function test_publish_toggle_controls_listing_download_and_admin_access(): void
    {
        $record = $this->create($this->pdf());
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $admin = auth()->user();
        $this->actingAs($member->user)->get('/anggota/dokumen')->assertOk()->assertDontSee($record->judul);
        $this->get(route('anggota.dokumen.download', $record->id))->assertNotFound();
        $this->get('/admin/dokumen')->assertForbidden();
        $this->get('/admin/dokumen/create')->assertForbidden();
        $this->actingAs($admin);
        Livewire::test(ListDokumens::class)->set('activeTab', 'draf')->call('updateTableColumnState', 'published', (string) $record->id, true);
        $this->assertTrue($record->fresh()->published);
        $this->actingAs($member->user)->get('/anggota/dokumen')->assertOk()->assertSee($record->judul);
        $this->get(route('anggota.dokumen.download', $record->id))->assertDownload(basename($record->file_path));
    }
}
