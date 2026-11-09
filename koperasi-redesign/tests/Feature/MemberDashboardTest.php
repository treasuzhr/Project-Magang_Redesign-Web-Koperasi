<?php

namespace Tests\Feature;

use App\MemberOverview;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\ShuPeriode;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $name = 'Anggota Fiktif Pemilik'): Anggota
    {
        return Anggota::factory()->create([
            'user_id' => User::factory()->create(['name' => $name, 'must_change_password' => false])->id,
            'nama' => $name,
        ]);
    }

    private function saving(Anggota $member, string $jenis, string $tipe, int $nominal): void
    {
        $member->simpananTransaksis()->create([
            'jenis' => $jenis, 'tipe' => $tipe, 'nominal' => $nominal,
            'tanggal' => '2025-01-01', 'sumber' => 'manual',
        ]);
    }

    private function loan(Anggota $member, string $status, int $amount, string $code): Pinjaman
    {
        $kind = PinjamanJenis::firstOrCreate(['kode' => 'usipa'], [
            'nama' => 'Unit Simpan Pinjam', 'bunga_persen_per_bulan' => '1.0000',
            'plafon_maks' => 50000000, 'tenor_maks' => 36, 'butuh_dokumen_akad' => false,
        ]);

        return $member->pinjamans()->create([
            'pinjaman_jenis_id' => $kind->id, 'kode' => $code, 'jumlah' => $amount,
            'tenor_bulan' => 5, 'bunga_persen_per_bulan' => '1.0000',
            'metode_bayar' => 'potong_gaji', 'status' => $status, 'tanggal_pengajuan' => '2025-01-01',
        ]);
    }

    private function allocation(Anggota $member, ShuPeriode $period, string $code, int $amount): void
    {
        $component = $period->komponens()->firstOrCreate(['kode' => $code], [
            'unit' => 'toko', 'metode' => 'proporsional', 'pool_nominal' => 1000000,
        ]);
        $component->shuAnggotas()->create([
            'shu_periode_id' => $period->id, 'anggota_id' => $member->id,
            'kode_komponen' => $code, 'jumlah_dasar' => 100000, 'bulan_aktif' => 12, 'shu' => $amount,
        ]);
    }

    public function test_demo_login_dashboard_matches_the_seeded_member_ledger_and_loan_totals(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        // Complete the password prerequisite only in this isolated test database.
        $user->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', [
            'member_id' => $user->nip, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE',
        ])->assertRedirect('/anggota');
        $response = $this->get('/anggota')->assertOk();
        $response->assertViewHas('summary', ['pokok' => 100000, 'wajib' => 4200000, 'sukarela' => 2875000, 'khusus' => 0])
            ->assertViewHas('totalSimpanan', 7175000)
            ->assertSee('data-count-value="7175000"', false)->assertSee('Rp 7.175.000')
            ->assertSee('Rp 4.200.000')->assertSee('Rp 2.875.000')->assertDontSee('8.164.614');
        $this->assertSame([0, 5000000, 0], array_column($response->viewData('stats')['pinjaman'], 'amount'));
        $this->assertSame($user->nip, $response->viewData('member')['nip']);
        $this->assertSame('Belum diisi', $response->viewData('member')['jabatan']);
        $direct = DB::table('simpanan_transaksis')->where('anggota_id', $user->anggota->id)
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN nominal ELSE -nominal END) AS total")->value('total');
        $this->assertSame((int) $direct, $response->viewData('totalSimpanan'));
    }

    public function test_all_four_savings_balances_subtract_withdrawals_and_update_both_charts(): void
    {
        $member = $this->member();
        foreach (['pokok' => [100000, 25000], 'wajib' => [300000, 100000], 'sukarela' => [250000, 50000], 'khusus' => [90000, 20000]] as $type => [$deposit, $withdrawal]) {
            $this->saving($member, $type, 'setor', $deposit);
            $this->saving($member, $type, 'tarik', $withdrawal);
        }
        $response = $this->actingAs($member->user)->get('/anggota')->assertOk();
        $response->assertViewHas('summary', ['pokok' => 75000, 'wajib' => 200000, 'sukarela' => 200000, 'khusus' => 70000])
            ->assertViewHas('totalSimpanan', 545000)->assertSee('Rp 545.000')->assertSee('Rp 75.000');
        $composition = $response->viewData('composition');
        $this->assertSame([13.8, 36.7, 36.7, 12.8], array_column($composition, 'percent'));
        $this->assertSame(2, substr_count($response->getContent(), 'aria-label="'.$response->viewData('compositionLabel').'"'));
        $this->assertStringContainsString('pokok 13,8 persen', $response->viewData('compositionLabel'));
    }

    public function test_dashboard_ignores_other_member_identifiers_and_financial_records(): void
    {
        $owner = $this->member('Anggota Fiktif Pemilik');
        $other = $this->member('Anggota Fiktif Rahasia');
        $this->saving($owner, 'wajib', 'setor', 123000);
        $this->saving($other, 'wajib', 'setor', 987654321);
        $this->loan($owner, 'berjalan', 1000000, 'OWNER-LOAN');
        $this->loan($other, 'berjalan', 3333333, 'OTHER-SECRET-LOAN');
        $this->loan($other, 'diajukan', 4444444, 'OTHER-PENDING');
        $response = $this->actingAs($owner->user)->get('/anggota?anggota_id='.$other->id.'&user_id='.$other->user_id)->assertOk();
        $response->assertViewHas('totalSimpanan', 123000)
            ->assertDontSee($other->nama)->assertDontSee('987.654.321')->assertDontSee('3.333.333')->assertDontSee('4.444.444');
        $this->assertSame([1000000, 1000000, 0], array_column($response->viewData('stats')['pinjaman'], 'amount'));
        $overview = app(MemberOverview::class);
        $this->assertSame(['OWNER-LOAN'], $overview->activeLoans($owner)->pluck('code')->all());
    }

    public function test_loan_summary_and_active_loan_details_use_paid_principal_and_unpaid_installments(): void
    {
        $member = $this->member();
        $active = $this->loan($member, 'berjalan', 1000000, 'ACTIVE');
        foreach (['lunas', 'tunggak', 'belum'] as $index => $status) {
            $active->angsurans()->create([
                'angsuran_ke' => $index + 1, 'jatuh_tempo' => '2025-0'.($index + 1).'-15',
                'pokok' => 200000, 'bunga' => 10000, 'total' => 210000, 'status' => $status,
                'tanggal_bayar' => $status === 'lunas' ? '2025-01-15' : null,
            ]);
        }
        $this->loan($member, 'lunas', 800000, 'PAID');
        $this->loan($member, 'diajukan', 250000, 'PENDING');
        $this->loan($member, 'disetujui', 400000, 'APPROVED');
        $this->loan($member, 'ditolak', 900000, 'REJECTED');
        $overview = app(MemberOverview::class);
        $this->assertSame(['active_remaining' => 800000, 'disbursed_total' => 1800000, 'pending_total' => 250000], $overview->loanSummary($member));
        $detail = $overview->activeLoans($member)->sole();
        $this->assertSame(800000, $detail['remaining_principal']);
        $this->assertSame('usipa', $detail['type']);
        $this->assertSame(1, $detail['overdue_count']);
        $this->assertSame(['number' => 2, 'due_date' => '2025-02-15', 'amount' => 210000], $detail['next_installment']);
        $response = $this->actingAs($member->user)->get('/anggota')->assertOk();
        $this->assertSame([800000, 1800000, 250000], array_column($response->viewData('stats')['pinjaman'], 'amount'));
    }

    public function test_empty_dashboard_has_zero_balances_and_queries_no_absent_blocks(): void
    {
        $member = $this->member();
        DB::enableQueryLog();
        $response = $this->actingAs($member->user)->get('/anggota')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        DB::disableQueryLog();
        $response->assertViewHas('totalSimpanan', 0)->assertSee('Rp 0')->assertDontSee('NaN')->assertDontSee('Infinity');
        $this->assertSame([0, 0, 0, 0], array_values($response->viewData('summary')));
        $this->assertSame([0.0, 0.0, 0.0, 0.0], array_column($response->viewData('composition'), 'percent'));
        $this->assertSame([0, 0, 0], array_column($response->viewData('stats')['pinjaman'], 'amount'));
        $this->assertStringNotContainsString('shu_anggotas', $queries);
        $this->assertStringNotContainsString('pesanans', $queries);
        $overview = app(MemberOverview::class);
        $this->assertCount(0, $overview->activeLoans($member));
        $this->assertNull($overview->latestShu($member));
        $this->assertCount(0, $overview->shuHistory($member));
        $this->assertCount(0, $overview->recentOrders($member));
    }

    public function test_reusable_shu_queries_exclude_drafts_and_other_member_allocations(): void
    {
        $member = $this->member();
        $other = $this->member('Anggota Fiktif Lain');
        $older = ShuPeriode::create(['tahun' => 2023, 'status' => 'ditetapkan']);
        $latest = ShuPeriode::create(['tahun' => 2024, 'status' => 'ditetapkan']);
        $draft = ShuPeriode::create(['tahun' => 2025, 'status' => 'draft']);
        $this->allocation($member, $older, 'simpanan_pokok_wajib', 100000);
        $this->allocation($member, $latest, 'simpanan_pokok_wajib', 300000);
        $this->allocation($member, $latest, 'belanja', 20000);
        $this->allocation($other, $latest, 'simpanan_pokok_wajib', 900000);
        $this->allocation($member, $draft, 'simpanan_pokok_wajib', 999999);
        $overview = app(MemberOverview::class);
        $this->assertSame(['year' => 2024, 'amount' => 320000], $overview->latestShu($member));
        $this->assertSame([['year' => 2024, 'amount' => 320000], ['year' => 2023, 'amount' => 100000]], $overview->shuHistory($member)->all());
        $newMember = $this->member('Anggota Fiktif Tanpa SHU');
        $this->assertNull($overview->latestShu($newMember));
    }

    public function test_recent_orders_are_scoped_sorted_and_limited_without_loading_other_members(): void
    {
        $member = $this->member();
        $other = $this->member('Anggota Fiktif Lain');
        foreach ([['OLD', '2024-01-01', 'selesai'], ['LATEST', '2025-03-01', 'baru'], ['MIDDLE', '2025-02-01', 'diproses']] as [$code, $date, $status]) {
            $order = $member->pesanans()->create(['kode' => $code, 'tanggal' => $date, 'status' => $status, 'total' => 10000]);
            $order->items()->create(['nama' => 'Produk Fiktif', 'harga' => 5000, 'qty' => 2, 'subtotal' => 10000]);
        }
        $other->pesanans()->create(['kode' => 'OTHER-SECRET', 'tanggal' => '2026-01-01', 'status' => 'baru', 'total' => 999999]);
        $orders = app(MemberOverview::class)->recentOrders($member, 2);
        $this->assertSame(['LATEST', 'MIDDLE'], $orders->pluck('code')->all());
        $this->assertSame(['baru', 'diproses'], $orders->pluck('status')->all());
        $this->assertSame('01 Maret 2025', $orders[0]['date']);
        $this->assertSame(10000, $orders[0]['total']);
        $this->assertSame(1, $orders[0]['item_count']);
    }

    public function test_queries_remain_constant_as_the_number_of_active_loans_grows(): void
    {
        $member = $this->member();
        $member->load('user');
        $this->loan($member, 'berjalan', 1000000, 'ONE');
        $overview = app(MemberOverview::class);
        DB::enableQueryLog();
        $overview->activeLoans($member);
        $initialCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        for ($index = 0; $index < 5; $index++) {
            $this->loan($member, 'berjalan', 1000000, 'MORE-'.$index);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        Model::preventLazyLoading();
        try {
            $this->assertCount(6, $overview->activeLoans($member));
            $this->assertSame($initialCount, count(DB::getQueryLog()));
            DB::flushQueryLog();
            $overview->dashboard($member);
            $this->assertCount(2, DB::getQueryLog());
        } finally {
            Model::preventLazyLoading(false);
            DB::disableQueryLog();
        }
    }

    public function test_active_loan_without_unpaid_installments_has_no_next_due_date(): void
    {
        $member = $this->member();
        $this->loan($member, 'berjalan', 1000000, 'NO-INSTALLMENTS');
        $detail = app(MemberOverview::class)->activeLoans($member)->sole();
        $this->assertNull($detail['next_installment']);
        $this->assertSame(0, $detail['overdue_count']);
        $this->assertSame(1000000, $detail['remaining_principal']);
    }

    public function test_guests_and_admins_cannot_access_the_dashboard(): void
    {
        $this->get('/anggota')->assertRedirect('/login');
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($admin)->get('/anggota')->assertForbidden();
    }
}
