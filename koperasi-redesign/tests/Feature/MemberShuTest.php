<?php

namespace Tests\Feature;

use Tests\TestCase;

class MemberShuTest extends TestCase
{
    public function test_shu_index_shows_the_latest_summary_and_ordered_member_data(): void
    {
        $response = $this->withSession(['member.authenticated' => true])
            ->get('/anggota/shu');

        $response
            ->assertOk()
            ->assertSee('Data SHU')
            ->assertSee('Sisa Hasil Usaha Anda per tahun.')
            ->assertSee('SHU tahun 2025')
            ->assertSee('Rp 676.717')
            ->assertSee('1990••••••••••1001')
            ->assertSee('Agus Wiji Suhariono, S.Kom.')
            ->assertSee('aria-label="Lihat detail SHU tahun 2025"', false)
            ->assertSee('Grafik SHU: 2021 Rp 407.235, 2022 Rp 244.835, 2023 Rp 422.762, 2024 Rp 619.000, 2025 Rp 676.717', false);

        $this->assertSame(5, substr_count($response->getContent(), '<tr data-table-row>'));
    }

    public function test_shu_detail_shows_exact_2025_rows_and_fixed_total(): void
    {
        $response = $this->withSession(['member.authenticated' => true])
            ->get('/anggota/shu/2025');

        $response
            ->assertOk()
            ->assertSee('Detail ')
            ->assertSee('SHU tahun 2025')
            ->assertSee('Simpanan Pokok')
            ->assertSee('Jasa Sipa Angg')
            ->assertSee('Jasa Sipa Sus')
            ->assertSee('SHU Seluruhnya')
            ->assertSee('Rp 676.717')
            ->assertSee('class="shu-table-total"', false)
            ->assertDontSee('Tampilkan');
    }

    public function test_other_listed_years_show_an_empty_detail_and_unknown_years_return_404(): void
    {
        $this->withSession(['member.authenticated' => true])
            ->get('/anggota/shu/2024')
            ->assertOk()
            ->assertSee('Detail belum tersedia.');

        $this->withSession(['member.authenticated' => true])
            ->get('/anggota/shu/2020')
            ->assertNotFound();
    }

    public function test_shu_pages_require_a_member_session(): void
    {
        $this->get('/anggota/shu')->assertRedirect('/login');
        $this->get('/anggota/shu/2025')->assertRedirect('/login');
    }
}
