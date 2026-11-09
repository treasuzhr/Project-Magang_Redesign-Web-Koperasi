<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_history_shows_the_four_orders_in_order(): void
    {
        $response = $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/pesanan');

        $response
            ->assertOk()
            ->assertSee('Data riwayat')
            ->assertSee('Daftar belanja Anda di Def.MART.')
            ->assertSee('01 Februari 2026')
            ->assertSee('15 November 2023')
            ->assertSee('19 Maret 2023')
            ->assertSee('Rp 1.000')
            ->assertSee('Rp 21.000')
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 447.000')
            ->assertSee('Menampilkan 1-4 dari 4 data')
            ->assertSee('aria-label="Lihat detail order tanggal 01 Februari 2026"', false)
            ->assertSee('href="'.route('anggota.pesanan.detail', ['id' => 1]).'"', false)
            ->assertSee('aria-current="page"', false);

        $this->assertSame(4, substr_count($response->getContent(), '<tr data-table-row>'));
    }

    public function test_order_details_show_product_data_only_when_available(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/pesanan/1')
            ->assertOk()
            ->assertSee('Detail')
            ->assertSee('order')
            ->assertSee('Order tanggal 01 Februari 2026.')
            ->assertSee('AMPLOP COKLAT A3')
            ->assertSee('Rp 1.000')
            ->assertSee('Nama produk')
            ->assertSee('Jumlah');

        foreach ([2, 3, 4] as $id) {
            $this->memberSession(['member.authenticated' => true])
                ->get("/anggota/pesanan/{$id}")
                ->assertOk()
                ->assertSee('Detail belum tersedia.')
                ->assertDontSee('AMPLOP COKLAT A3');
        }
    }

    public function test_unknown_order_ids_return_not_found_and_pages_require_member_session(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/pesanan/5')
            ->assertNotFound();

        $this->memberSession(['member.authenticated' => false])
            ->get('/anggota/pesanan')
            ->assertRedirect('/login');
        $this->get('/anggota/pesanan/1')->assertRedirect('/login');
    }
}
