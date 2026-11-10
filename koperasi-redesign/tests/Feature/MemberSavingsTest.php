<?php

namespace Tests\Feature;

use App\MemberOverview;
use App\Models\Anggota;
use App\Models\SimpananTransaksi;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MemberSavingsTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $name = 'Anggota Fiktif Pemilik'): Anggota
    {
        return Anggota::factory()->create([
            'user_id' => User::factory()->create(['name' => $name, 'must_change_password' => false])->id,
            'nama' => $name,
        ]);
    }

    private function transaction(Anggota $member, string $jenis, string $tipe, int $nominal, string $date = '2025-01-01'): SimpananTransaksi
    {
        return $member->simpananTransaksis()->create([
            'jenis' => $jenis, 'tipe' => $tipe, 'nominal' => $nominal, 'tanggal' => $date, 'sumber' => 'manual',
        ]);
    }

    public function test_demo_login_and_all_tabs_use_the_seeded_member_data(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        $user->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', [
            'member_id' => $user->nip, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE',
        ])->assertRedirect('/anggota');
        $this->get('/anggota/simpanan')->assertRedirect('/anggota/simpanan/pokok');
        foreach (['pokok', 'wajib', 'sukarela', 'khusus', 'setor', 'tarik'] as $type) {
            $response = $this->get('/anggota/simpanan/'.$type)->assertOk();
            $response->assertViewHas('summary', ['pokok' => 100000, 'wajib' => 4200000, 'sukarela' => 2875000, 'khusus' => 0])
                ->assertViewHas('totalSimpanan', 7175000)->assertDontSee('data-member-table=', false);
            $this->assertSame($user->nip, $response->viewData('member')['nip']);
            $this->assertCount(6, $response->viewData('tabs'));
            if ($type === 'pokok') {
                $this->assertSame(2021, $response->viewData('rows')[0]['year']);
                $response->assertSee('Januari')->assertSee('Rp 100.000');
            } elseif ($type === 'khusus') {
                $response->assertSee('Belum ada data.');
            } else {
                $expected = match ($type) {
                    'wajib' => 1200000, 'sukarela' => 575000, 'setor' => 600000, default => -25000
                };
                $this->assertSame($expected, $response->viewData('rows')[0]['amount']);
                $this->assertSame(2025, $response->viewData('rows')[0]['year']);
            }
        }
    }

    public function test_savings_balance_subtracts_withdrawals_for_every_type(): void
    {
        $member = $this->member();
        foreach (['pokok', 'wajib', 'sukarela', 'khusus'] as $type) {
            $this->transaction($member, $type, 'setor', 100000);
            $this->transaction($member, $type, 'tarik', 25000);
        }
        $this->actingAs($member->user)->get('/anggota/simpanan/sukarela')->assertOk()
            ->assertViewHas('summary', ['pokok' => 75000, 'wajib' => 75000, 'sukarela' => 75000, 'khusus' => 75000])
            ->assertViewHas('totalSimpanan', 300000)->assertSee('Rp 75.000');
    }

    public function test_query_filters_redirect_and_keep_year_type_and_page_size(): void
    {
        $member = $this->member();
        $this->transaction($member, 'sukarela', 'setor', 100000, '2024-01-01');
        $this->transaction($member, 'sukarela', 'tarik', 25000, '2025-01-01');
        $this->actingAs($member->user)->get('/anggota/simpanan?jenis=sukarela&tipe=tarik&tahun=2025&per_page=25')
            ->assertRedirect(route('anggota.simpanan.tab', ['tipe' => 'tarik', 'tahun' => 2025, 'per_page' => 25, 'jenis' => 'sukarela']));
        $response = $this->get('/anggota/simpanan/pokok?jenis=sukarela&tipe=tarik&tahun=2025&per_page=25')->assertOk();
        $this->assertSame('sukarela', $response->viewData('jenis'));
        $this->assertSame([2025], array_column($response->viewData('rows'), 'year'));
        $this->assertSame(-25000, $response->viewData('rows')[0]['amount']);
        $response->assertSee('Rp -25.000')->assertSee('tipe=tarik', false);
        $detail = $this->get('/anggota/simpanan/sukarela/2025?tipe=tarik&per_page=25')->assertOk();
        $this->assertSame(1, $detail->viewData('paginator')->total());
        $this->assertSame(75000, $detail->viewData('rows')[0]['running_balance']);
    }

    public function test_detail_pagination_runs_on_server_and_preserves_filters_and_row_numbers(): void
    {
        $member = $this->member();
        $this->transaction($member, 'sukarela', 'setor', 100000, '2024-12-31');
        for ($day = 1; $day <= 26; $day++) {
            $this->transaction($member, 'sukarela', 'setor', $day * 1000, sprintf('2025-01-%02d', $day));
        }
        $this->transaction($member, 'sukarela', 'tarik', 500, '2025-01-15');
        $response = $this->actingAs($member->user)->get('/anggota/simpanan/sukarela/2025?tipe=setor&per_page=10&page=2')->assertOk();
        $paginator = $response->viewData('paginator');
        $this->assertSame(26, $paginator->total());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertCount(10, $response->viewData('rows'));
        $this->assertSame(range(11, 20), array_column($response->viewData('rows'), 'number'));
        $this->assertSame(16000, $response->viewData('rows')[0]['amount']);
        $this->assertSame(235500, $response->viewData('rows')[0]['running_balance']);
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $next);
        $this->assertSame(['tipe' => 'setor', 'per_page' => '10', 'page' => '3'], $next);
        $response->assertSee('Menampilkan 11-20 dari 26 data')->assertSee('rel="next"', false)->assertDontSee('data-member-table=', false);
        $this->assertSame(10, substr_count($response->getContent(), '<tr data-table-row>'));
        $last = $this->get('/anggota/simpanan/sukarela/2025?tipe=setor&per_page=10&page=3')->assertOk();
        $this->assertCount(6, $last->viewData('rows'));
        $this->assertSame(26, $last->viewData('rows')[5]['number']);
    }

    public function test_running_balance_is_partitioned_and_keeps_opening_balances_before_filters(): void
    {
        $member = $this->member();
        $other = $this->member('Anggota Fiktif Lain');
        $first = $this->transaction($member, 'sukarela', 'setor', 100, '2024-01-01');
        $withdrawal = $this->transaction($member, 'sukarela', 'tarik', 30, '2024-01-01');
        $third = $this->transaction($member, 'sukarela', 'setor', 50, '2024-01-01');
        $last = $this->transaction($member, 'sukarela', 'setor', 20, '2025-01-01');
        $this->transaction($member, 'wajib', 'setor', 999000, '2023-01-01');
        $this->transaction($other, 'sukarela', 'setor', 888000, '2023-01-01');
        $overview = app(MemberOverview::class);
        DB::enableQueryLog();
        $rows = $overview->savingsHistory($member, 'sukarela')->items();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        DB::disableQueryLog();
        $this->assertSame([$last->id, $third->id, $withdrawal->id, $first->id], array_column($rows, 'id'));
        $this->assertSame([140, 120, 70, 100], array_column($rows, 'running_balance'));
        $this->assertStringContainsString('OVER (PARTITION BY jenis ORDER BY tanggal, id ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)', $queries);
        $filtered = $overview->savingsHistory($member, 'sukarela', 'tarik', 2024)->items();
        $this->assertSame(70, $filtered[0]['running_balance']);
        $this->assertSame(-30, $filtered[0]['amount']);
        $this->assertSame(140, $overview->savingsHistory($member, 'sukarela', 'setor', 2025)->items()[0]['running_balance']);
    }

    public function test_annual_recap_uses_sql_net_amounts_and_server_pagination(): void
    {
        $member = $this->member();
        for ($year = 2010; $year <= 2024; $year++) {
            $this->transaction($member, 'wajib', 'setor', $year * 100, $year.'-01-01');
        }
        $this->transaction($member, 'wajib', 'tarik', 20000, '2024-02-01');
        $first = $this->actingAs($member->user)->get('/anggota/simpanan/wajib')->assertOk();
        $this->assertSame(15, $first->viewData('paginator')->total());
        $this->assertSame(182400, $first->viewData('rows')[0]['amount']);
        $this->assertCount(10, $first->viewData('rows'));
        $last = $this->get('/anggota/simpanan/wajib?page=2&per_page=10')->assertOk();
        $this->assertSame([2014, 2013, 2012, 2011, 2010], array_column($last->viewData('rows'), 'year'));
        $this->assertSame(range(11, 15), array_column($last->viewData('rows'), 'number'));
        $filtered = $this->get('/anggota/simpanan/wajib?tahun=2024')->assertOk();
        $this->assertSame(1, $filtered->viewData('paginator')->total());
        $this->assertSame(182400, $filtered->viewData('rows')[0]['amount']);
    }

    public function test_only_logged_in_member_transactions_and_profile_are_used(): void
    {
        $member = $this->member('Anggota Fiktif Pemilik');
        $other = $this->member('Anggota Fiktif Rahasia');
        $own = $this->transaction($member, 'sukarela', 'setor', 123000);
        $this->transaction($other, 'sukarela', 'setor', 987654321);
        $this->transaction($other, 'sukarela', 'setor', 999999, '2030-01-01');
        $response = $this->actingAs($member->user)->get('/anggota/simpanan/sukarela/2025?anggota_id='.$other->id.'&user_id='.$other->user_id)->assertOk();
        $this->assertSame([$own->id], array_column($response->viewData('rows'), 'id'));
        $response->assertSee($member->nama)->assertDontSee($other->nama)->assertDontSee('987.654.321');
        $this->assertSame($member->user->nip, $response->viewData('member')['nip']);
        $this->get('/anggota/simpanan/sukarela?tahun=2030')->assertOk()->assertSee('Belum ada data.');
    }

    public function test_empty_tabs_and_detail_years_render_without_errors(): void
    {
        $member = $this->member();
        $this->actingAs($member->user);
        foreach (['pokok', 'wajib', 'sukarela', 'khusus', 'setor', 'tarik'] as $type) {
            $response = $this->get('/anggota/simpanan/'.$type)->assertOk()->assertSee('Belum ada data.')->assertSee('Menampilkan 0-0 dari 0 data');
            $this->assertSame(0, $response->viewData('totalSimpanan'));
        }
        $this->get('/anggota/simpanan/sukarela/2019')->assertOk()->assertSee('Belum ada data.');
    }

    public function test_invalid_filters_and_unsupported_detail_types_are_rejected(): void
    {
        $member = $this->member();
        $this->actingAs($member->user);
        foreach (['jenis=invalid', 'tipe=invalid', 'tahun=abc', 'tahun=1800', 'per_page=7', 'page=0'] as $query) {
            $this->getJson('/anggota/simpanan/pokok?'.$query)->assertUnprocessable();
        }
        $this->getJson('/anggota/simpanan/setor?tipe=tarik')->assertUnprocessable();
        $this->get('/anggota/simpanan/invalid')->assertNotFound();
        $this->get('/anggota/simpanan/pokok/2025')->assertNotFound();
    }

    public function test_setor_and_tarik_are_read_only_history_not_transaction_forms(): void
    {
        $member = $this->member();
        $this->transaction($member, 'sukarela', 'setor', 100000);
        $this->transaction($member, 'sukarela', 'tarik', 25000);
        $this->actingAs($member->user)->get('/anggota/simpanan/setor')->assertOk()->assertSee('Lihat detail');
        $count = SimpananTransaksi::count();
        $this->post('/anggota/simpanan/setor', ['nominal' => 999999])->assertStatus(405);
        $this->post('/anggota/simpanan/tarik', ['nominal' => 999999])->assertStatus(405);
        $this->assertSame($count, SimpananTransaksi::count());
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'anggota/simpanan')) {
                $this->assertSame([], array_values(array_diff($route->methods(), ['GET', 'HEAD'])));
            }
        }
    }

    public function test_savings_pages_require_authentication_and_member_role(): void
    {
        foreach (['/anggota/simpanan', '/anggota/simpanan/pokok', '/anggota/simpanan/sukarela/2025'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        foreach (['/anggota/simpanan', '/anggota/simpanan/pokok', '/anggota/simpanan/sukarela/2025'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
    }
}
