<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Berita;
use App\Models\Dokumen;
use App\Models\Kategori;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\Produk;
use App\Models\ShuAnggota;
use App\Models\ShuKomponen;
use App\Models\ShuPeriode;
use App\Models\SimpananTransaksi;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CooperativeDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_accounts_and_original_catalog_and_news_are_preserved(): void
    {
        $this->seedDemo();
        $this->assertSame(21, User::count());
        $this->assertSame(20, Anggota::count());
        $this->assertSame(9, Kategori::count());
        $this->assertSame(15, Produk::count());
        $this->assertSame(3, Berita::count());
        $this->assertSame(5, Dokumen::count());
        foreach ([CooperativeDemoSeeder::ADMIN_NIP => 'admin', CooperativeDemoSeeder::ANGGOTA_NIP => 'anggota'] as $nip => $role) {
            $user = User::where('nip', $nip)->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->must_change_password);
            $this->assertTrue(Hash::check(CooperativeDemoSeeder::PASSWORD, $user->password));
            $this->assertArrayNotHasKey('password', $user->toArray());
        }
        foreach (config('product_catalog.samples') as $sample) {
            $this->assertDatabaseHas('produks', ['nama' => $sample['name'], 'harga' => $sample['price']]);
        }
        foreach (config('news.articles') as $article) {
            $saved = Berita::where('judul', $article['title'])->firstOrFail();
            $this->assertSame($article['sections'], $saved->isi['sections']);
        }
        $this->assertSame(0, Dokumen::where('published', true)->count());
        $this->assertTrue(User::all()->every(fn (User $user): bool => str_starts_with($user->nip, 'DEMO-')));
    }

    public function test_seeding_twice_preserves_existing_accounts_and_does_not_duplicate_transactions(): void
    {
        $existing = User::factory()->create(['nip' => 'EXISTING-FIKTIF', 'name' => 'Existing Demo']);
        $this->seedDemo();
        $password = User::where('nip', CooperativeDemoSeeder::ADMIN_NIP)->value('password');
        $counts = [SimpananTransaksi::count(), Pinjaman::count(), Angsuran::count(), Pesanan::count(), ShuAnggota::count()];
        $this->seedDemo();
        $this->assertSame($counts, [SimpananTransaksi::count(), Pinjaman::count(), Angsuran::count(), Pesanan::count(), ShuAnggota::count()]);
        $this->assertSame($password, User::where('nip', CooperativeDemoSeeder::ADMIN_NIP)->value('password'));
        $this->assertSame('Existing Demo', $existing->fresh()->name);
    }

    public function test_savings_accessors_use_the_ledger_and_include_withdrawals_and_cutoff(): void
    {
        $member = Anggota::factory()->create();
        foreach ([['setor', 100000, '2024-01-01'], ['tarik', 25000, '2024-02-01'], ['setor', 50000, '2025-01-01']] as [$tipe, $nominal, $tanggal]) {
            $member->simpananTransaksis()->create(compact('tipe', 'nominal', 'tanggal') + ['jenis' => 'sukarela', 'sumber' => 'manual']);
        }
        $this->assertSame(125000, $member->saldo_sukarela);
        $this->assertSame(75000, $member->saldoSimpanan('sukarela', '2024-12-31'));
        $this->assertSame(0, $member->saldo_pokok);
        $this->assertSame(0, $member->saldo_wajib);
        $this->assertSame(0, $member->saldo_khusus);
        $this->expectException(\InvalidArgumentException::class);
        $member->saldoSimpanan('unknown');
    }

    public function test_demo_ledger_respects_membership_dates_and_monthly_savings_rates(): void
    {
        $this->seedDemo();
        $member = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail()->anggota;
        $this->assertSame(100000, $member->saldo_pokok);
        $this->assertSame(4200000, $member->saldo_wajib);
        $this->assertSame(2875000, $member->saldo_sukarela);
        foreach (Anggota::all() as $item) {
            $this->assertSame(1, $item->simpananTransaksis()->where('jenis', 'pokok')->count());
            $this->assertSame(0, $item->simpananTransaksis()->whereDate('tanggal', '<', $item->tanggal_masuk)->count());
            if ($item->tanggal_keluar !== null) {
                $this->assertSame(0, $item->simpananTransaksis()->whereDate('tanggal', '>', $item->tanggal_keluar)->count());
            }
        }
    }

    public function test_flat_installments_match_principal_interest_and_loan_status(): void
    {
        $this->seedDemo();
        $this->assertSame(3, PinjamanJenis::count());
        foreach (Pinjaman::with(['jenis', 'angsurans'])->get() as $loan) {
            $this->assertTrue($loan->anggota()->exists());
            if (in_array($loan->status, ['diajukan', 'ditolak'], true)) {
                $this->assertCount(0, $loan->angsurans);

                continue;
            }
            $this->assertCount(10, $loan->angsurans);
            $this->assertSame($loan->jumlah, (int) $loan->angsurans->sum('pokok'));
            $expectedInterest = $loan->jenis->kode === 'toko' ? 20000 : 10000;
            foreach ($loan->angsurans as $payment) {
                $this->assertSame($expectedInterest, $payment->bunga);
                $this->assertSame($payment->pokok + $payment->bunga, $payment->total);
                $this->assertSame($payment->status === 'lunas', $payment->tanggal_bayar !== null);
                $this->assertSame($loan->id, $payment->pinjaman->id);
            }
        }
        $loan = Pinjaman::whereHas('jenis', fn ($query) => $query->where('kode', 'usipa'))->firstOrFail();
        $loan->jenis->update(['bunga_persen_per_bulan' => '3.0000']);
        $this->assertSame('1.0000', $loan->fresh()->bunga_persen_per_bulan);
        foreach (Pinjaman::STATUSES as $status) {
            $this->assertTrue(Pinjaman::where('status', $status)->exists());
        }
        foreach (Angsuran::STATUSES as $status) {
            $this->assertTrue(Angsuran::where('status', $status)->exists());
        }
    }

    public function test_shu_uses_only_completed_shopping_paid_interest_and_prorated_membership(): void
    {
        $this->seedDemo();
        $this->assertSame(5, ShuPeriode::count());
        $this->assertSame(30, ShuKomponen::count());
        foreach (ShuPeriode::with(['komponens.shuAnggotas.anggota'])->get() as $period) {
            $this->assertSame((int) $period->shuAnggotas()->sum('shu'), $period->shu_bersih);
            foreach ($period->komponens as $component) {
                if ($component->metode === 'proporsional' && $component->shuAnggotas->sum('jumlah_dasar') > 0) {
                    $this->assertSame($component->pool_nominal, (int) $component->shuAnggotas->sum('shu'));
                }
                foreach ($component->shuAnggotas as $share) {
                    $this->assertSame($component->id, $share->komponen->id);
                    $this->assertSame($period->id, $share->periode->id);
                    $this->assertGreaterThanOrEqual(1, $share->bulan_aktif);
                    $this->assertLessThanOrEqual(12, $share->bulan_aktif);
                    if ($component->kode === 'belanja') {
                        $orders = $share->anggota->pesanans()->where('status', 'selesai')->whereYear('tanggal', $period->tahun)->sum('total');
                        $this->assertSame((int) $orders, $share->jumlah_dasar);
                    }
                    if ($component->metode === 'persentase_tetap') {
                        $base = Angsuran::whereHas('pinjaman', fn ($query) => $query->where('anggota_id', $share->anggota_id)
                            ->whereHas('jenis', fn ($kind) => $kind->where('kode', 'usipa')))
                            ->where('status', 'lunas')->whereYear('tanggal_bayar', $period->tahun)->sum('bunga');
                        $this->assertSame((int) $base, $share->jumlah_dasar);
                        $this->assertSame(intdiv($share->jumlah_dasar * 275, 1000), $share->shu);
                    }
                    if ($component->metode === 'bagi_rata_prorata') {
                        $this->assertSame(intdiv($component->pool_nominal * $share->bulan_aktif, $component->shuAnggotas->count() * 12), $share->shu);
                    }
                }
            }
        }
        $partial = User::where('nip', 'DEMO-ANGGOTA-019')->firstOrFail()->anggota;
        $this->assertSame(6, $partial->shuAnggotas()->whereHas('periode', fn ($query) => $query->where('tahun', 2025))->where('kode_komponen', 'jasa_umum')->firstOrFail()->bulan_aktif);
        $this->assertSame(0, $partial->shuAnggotas()->whereHas('periode', fn ($query) => $query->where('tahun', '<', 2025))->count());
    }

    public function test_order_snapshots_totals_and_special_savings_match_source_records(): void
    {
        $this->seedDemo();
        foreach (Pesanan::with('items.produk')->get() as $order) {
            $this->assertSame((int) $order->items->sum('subtotal'), $order->total);
            foreach ($order->items as $item) {
                $this->assertSame($item->harga * $item->qty, $item->subtotal);
                $this->assertSame($order->id, $item->pesanan->id);
                $this->assertNotNull($item->produk);
            }
        }
        foreach (SimpananTransaksi::where('jenis', 'khusus')->get() as $transaction) {
            $share = ShuAnggota::where('anggota_id', $transaction->anggota_id)->where('kode_komponen', 'jasa_usipa_khusus')
                ->whereHas('periode', fn ($query) => $query->where('tahun', $transaction->tanggal->year - 1)->where('status', 'ditetapkan'))->firstOrFail();
            $this->assertSame($share->shu, $transaction->nominal);
            $this->assertSame('shu', $transaction->sumber);
        }
    }

    public function test_foreign_keys_prevent_orphaned_financial_records(): void
    {
        $this->expectException(QueryException::class);
        DB::table('simpanan_transaksis')->insert([
            'anggota_id' => 99999, 'jenis' => 'pokok', 'tipe' => 'setor',
            'nominal' => 100000, 'tanggal' => '2025-01-01', 'sumber' => 'manual',
        ]);
    }

    public function test_account_migration_preserves_legacy_users_and_their_passwords(): void
    {
        $user = User::factory()->create(['name' => 'Legacy Fiktif', 'email' => 'legacy@example.invalid']);
        $password = $user->password;
        $migration = require database_path('migrations/2026_11_09_022853_extend_users_for_cooperative_accounts.php');
        $migration->down();
        $migration->up();
        $saved = User::findOrFail($user->id);
        $this->assertSame('Legacy Fiktif', $saved->name);
        $this->assertSame('legacy@example.invalid', $saved->email);
        $this->assertSame($password, $saved->password);
        $this->assertSame('LEGACY-'.$user->id, $saved->nip);
        $this->assertSame('anggota', $saved->role);
    }

    public function test_unique_nip_is_enforced(): void
    {
        User::factory()->create(['nip' => 'DEMO-DUPLICATE']);
        $this->expectException(QueryException::class);
        User::factory()->create(['nip' => 'DEMO-DUPLICATE']);
    }

    public function test_shu_component_must_belong_to_the_same_period_and_code(): void
    {
        $this->seedDemo();
        $share = ShuAnggota::firstOrFail();
        $other = ShuKomponen::where('shu_periode_id', '!=', $share->shu_periode_id)->firstOrFail();
        $this->expectException(QueryException::class);
        $share->update(['shu_komponen_id' => $other->id]);
    }
}
