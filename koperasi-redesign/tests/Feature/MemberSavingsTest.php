<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSavingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_root_redirects_to_pokok_and_tabs_show_their_requested_data(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan')
            ->assertRedirect('/anggota/simpanan/pokok');

        $tabs = [
            '/anggota/simpanan/pokok' => ['Desember', 'Rp 100.000'],
            '/anggota/simpanan/wajib' => ['Rp 5.050.000', 'Lihat detail'],
            '/anggota/simpanan/sukarela' => ['Rp 2.100.000', 'Rp 950.000'],
            '/anggota/simpanan/khusus' => ['1990••••••••••1001', 'Rp 331.976'],
            '/anggota/simpanan/setor' => ['Belum ada data.'],
            '/anggota/simpanan/tarik' => ['September', 'Rp 2.500.000'],
        ];

        foreach ($tabs as $path => $expectedContent) {
            $response = $this->memberSession(['member.authenticated' => true])->get($path);
            $response->assertOk();

            foreach ($expectedContent as $text) {
                $response->assertSee($text);
            }
        }
    }

    public function test_savings_details_render_available_rows_and_empty_state_for_index_years(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/wajib/2025')
            ->assertOk()
            ->assertSee('Detail data')
            ->assertSee('Rp 5.050.000');

        $detail = $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/sukarela/2025');
        $detail
            ->assertOk()
            ->assertSee('Januari')
            ->assertSee('Desember')
            ->assertSee('Rp 200.000');
        $this->assertSame(12, substr_count($detail->getContent(), 'data-table-row'));

        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/sukarela/2024')
            ->assertOk()
            ->assertSee('Detail belum tersedia.');
    }

    public function test_unknown_savings_types_and_years_return_not_found(): void
    {
        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/invalid')
            ->assertNotFound();

        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/pokok/2025')
            ->assertNotFound();

        $this->memberSession(['member.authenticated' => true])
            ->get('/anggota/simpanan/sukarela/2019')
            ->assertNotFound();
    }

    public function test_savings_pages_require_member_session(): void
    {
        $this->get('/anggota/simpanan/pokok')
            ->assertRedirect('/login');

        $this->get('/anggota/simpanan/sukarela/2025')
            ->assertRedirect('/login');
    }
}
