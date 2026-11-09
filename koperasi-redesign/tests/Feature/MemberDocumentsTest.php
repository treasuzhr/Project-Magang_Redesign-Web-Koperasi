<?php

namespace Tests\Feature;

use Tests\TestCase;

class MemberDocumentsTest extends TestCase
{
    public function test_document_index_shows_ten_known_documents_and_marks_sidebar_active(): void
    {
        $response = $this->withSession(['member.authenticated' => true])
            ->get('/anggota/dokumen');

        $response
            ->assertOk()
            ->assertSee('Dokumen')
            ->assertSee('koperasi')
            ->assertSee('Unduh dokumen koperasi dalam format PDF.')
            ->assertSee('Menampilkan 1-10 dari 10 data')
            ->assertSee('BUKU RAT 2025 PRIMKOP PULLAHTA HANKAM')
            ->assertSee('REVISI RENCANA KERJA, ANGGARAN PENDAPATAN DAN BELANJA TAHUN 2022')
            ->assertSee('LAPORAN PERTANGGUNGJAWABAN PENGURUS KOPERASI PRIMKOP PULLAHTA HANKAM TUTUP BUKU TAHUN 2022')
            ->assertSee('LAPORAN PERTANGGUNGJAWABAN PENGURUS KOPERASI PRIMKOP PULLAHTA HANKAM TUTUP BUKU TAHUN 2021')
            ->assertSee('aria-label="Unduh PDF BUKU RAT 2025 PRIMKOP PULLAHTA HANKAM"', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('target="_blank"');

        $this->assertSame(10, substr_count($response->getContent(), '<tr data-table-row>'));
    }

    public function test_unavailable_document_redirects_with_flash_and_unknown_ids_return_not_found(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->get('/anggota/dokumen/1/unduh')
            ->assertRedirect('/anggota/dokumen')
            ->assertSessionHas('status', 'Berkas belum tersedia.');

        $this->withSession(['member.authenticated' => true, 'status' => 'Berkas belum tersedia.'])
            ->get('/anggota/dokumen')
            ->assertOk()
            ->assertSee('Berkas belum tersedia.')
            ->assertSee('role="status"', false);

        $this->withSession(['member.authenticated' => true])
            ->get('/anggota/dokumen/11/unduh')
            ->assertNotFound();
    }

    public function test_document_pages_require_a_member_session(): void
    {
        $this->get('/anggota/dokumen')->assertRedirect('/login');
        $this->get('/anggota/dokumen/1/unduh')->assertRedirect('/login');
    }
}
