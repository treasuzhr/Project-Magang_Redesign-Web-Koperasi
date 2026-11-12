<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberDocumentsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_private_file_streams_only_for_authenticated_member(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('documents/contoh.pdf', '%PDF-1.4 contoh pengujian');
        $doc = Dokumen::create(['judul' => 'Dokumen contoh', 'tahun' => 2024, 'published' => true, 'file_path' => 'documents/contoh.pdf']);
        $this->get('/anggota/dokumen/'.$doc->id.'/unduh')->assertRedirect('/login');
        $this->memberSession()->get('/anggota/dokumen')->assertOk()->assertSee(route('anggota.dokumen.download', ['id' => $doc->id]), false);
        $this->get('/anggota/dokumen/'.$doc->id.'/unduh')->assertDownload('contoh.pdf');
        $this->assertFalse(Storage::disk('public')->exists('documents/contoh.pdf'));
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]))->get('/anggota/dokumen/'.$doc->id.'/unduh')->assertForbidden();
        $this->get('/anggota/dokumen')->assertForbidden();
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
