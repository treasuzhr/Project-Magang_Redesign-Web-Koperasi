<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MemberDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function officeFile(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'member-document-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
        $zip->addFromString($extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml', '<?xml version="1.0"?><document/>');
        $zip->close();
        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }

    public function test_only_published_documents_are_paginated_newest_year_first(): void
    {
        Storage::fake('local');
        for ($year = 2000; $year <= 2025; $year++) {
            Dokumen::create(['judul' => 'Dokumen '.$year, 'tahun' => $year, 'published' => true]);
        }
        $hidden = Dokumen::create(['judul' => 'RAHASIA DRAFT', 'tahun' => 2030, 'published' => false]);
        $response = $this->memberSession()->get('/anggota/dokumen?per_page=10&page=2')->assertOk()->assertDontSee('RAHASIA DRAFT')->assertSee('Dokumen belum tersedia');
        $this->assertSame(26, $response->viewData('paginator')->total());
        $this->assertSame(range(2015, 2006), array_column($response->viewData('rows'), 'year'));
        $this->assertSame(range(11, 20), array_column($response->viewData('rows'), 'number'));
        $this->assertNull($response->viewData('rows')[0]['action_url']);
        $this->get('/anggota/dokumen/'.$hidden->id.'/unduh')->assertNotFound();
    }

    public function test_published_pdf_and_office_documents_show_the_correct_actions(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/contoh.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
        Storage::disk('local')->put('documents/laporan.docx', $this->officeFile('docx'));
        $pdf = Dokumen::create(['judul' => 'Dokumen PDF', 'tahun' => 2025, 'published' => true, 'file_path' => 'documents/contoh.pdf']);
        $docx = Dokumen::create(['judul' => 'Dokumen DOCX', 'tahun' => 2024, 'published' => true, 'file_path' => 'documents/laporan.docx']);

        $response = $this->memberSession()->get('/anggota/dokumen')->assertOk();
        $this->assertSame(['Lihat', 'Unduh'], array_column($response->viewData('rows')[0]['action_links'], 'label'));
        $this->assertSame(['Unduh'], array_column($response->viewData('rows')[1]['action_links'], 'label'));
        $response->assertSee(route('anggota.dokumen.view', ['id' => $pdf->id]), false)
            ->assertSee('target="_blank" rel="noopener"', false)
            ->assertSee(route('anggota.dokumen.download', ['id' => $docx->id]), false)
            ->assertDontSee(route('anggota.dokumen.view', ['id' => $docx->id]), false);
    }

    public function test_pdf_view_and_download_have_safe_headers_and_private_access(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('documents/contoh.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
        $doc = Dokumen::create(['judul' => 'Dokumen contoh', 'tahun' => 2024, 'published' => true, 'file_path' => 'documents/contoh.pdf']);

        $this->get(route('anggota.dokumen.view', ['id' => $doc->id]))->assertRedirect('/login');
        $this->memberSession()->get('/anggota/dokumen')->assertOk()->assertSee(route('anggota.dokumen.download', ['id' => $doc->id]), false);
        $this->get(route('anggota.dokumen.view', ['id' => $doc->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename=contoh.pdf');
        $this->get(route('anggota.dokumen.download', ['id' => $doc->id]))
            ->assertDownload('contoh.pdf')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=contoh.pdf');
        $this->assertFalse(Storage::disk('public')->exists('documents/contoh.pdf'));
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]))
            ->get(route('anggota.dokumen.download', ['id' => $doc->id]))
            ->assertForbidden();
        $this->get('/anggota/dokumen')->assertForbidden();
    }

    public function test_view_rejects_non_pdf_and_draft_documents_and_download_remains_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/bukan-pdf.pdf', 'isi bukan PDF');
        Storage::disk('local')->put('documents/laporan.docx', $this->officeFile('docx'));
        $fakePdf = Dokumen::create(['judul' => 'PDF palsu', 'tahun' => 2025, 'published' => true, 'file_path' => 'documents/bukan-pdf.pdf']);
        $docx = Dokumen::create(['judul' => 'Laporan', 'tahun' => 2024, 'published' => true, 'file_path' => 'documents/laporan.docx']);
        $draft = Dokumen::create(['judul' => 'Draf', 'tahun' => 2026, 'published' => false, 'file_path' => 'documents/laporan.docx']);

        $this->memberSession();
        $this->get(route('anggota.dokumen.view', ['id' => $fakePdf->id]))->assertNotFound();
        $this->get(route('anggota.dokumen.view', ['id' => $docx->id]))->assertNotFound();
        $this->get(route('anggota.dokumen.view', ['id' => $draft->id]))->assertNotFound();
        $this->get(route('anggota.dokumen.download', ['id' => $draft->id]))->assertNotFound();
        $this->get(route('anggota.dokumen.view', ['id' => 999999]))->assertNotFound();
        $this->get(route('anggota.dokumen.download', ['id' => 999999]))->assertNotFound();
        $this->get(route('anggota.dokumen.download', ['id' => $docx->id]))
            ->assertDownload('laporan.docx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=laporan.docx');

        $listing = $this->get('/anggota/dokumen')->assertOk();
        $this->assertSame(['Unduh'], array_column($listing->viewData('rows')[1]['action_links'], 'label'));
        $listing->assertDontSee(route('anggota.dokumen.view', ['id' => $docx->id]), false);
    }

    public function test_empty_missing_and_unsafe_paths_show_normal_unavailable_state(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('public-only.pdf', 'test');
        $this->memberSession();
        foreach ([null, '', 'missing.pdf', 'public-only.pdf', '../.env', 'C:\\secret.pdf'] as $index => $path) {
            $doc = Dokumen::create(['judul' => 'Tanpa file '.$index, 'tahun' => 2024, 'published' => true, 'file_path' => $path]);
            $this->get('/anggota/dokumen/'.$doc->id.'/unduh')->assertRedirect('/anggota/dokumen')->assertSessionHas('status', 'Dokumen belum tersedia');
        }
        $this->get('/anggota/dokumen')->assertOk()->assertSee('Dokumen belum tersedia')->assertDontSee('Unduh PDF Tanpa file');
        $this->get('/anggota/dokumen/999999/unduh')->assertNotFound();
    }

    public function test_empty_index_and_guest_access(): void
    {
        $this->get('/anggota/dokumen')->assertRedirect('/login');
        $this->memberSession()->get('/anggota/dokumen')->assertOk()->assertSee('Belum ada dokumen.')->assertSee('Menampilkan 0-0 dari 0 data');
    }
}
