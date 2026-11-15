<?php

namespace Tests\Feature;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\Pages\PreviewShuPeriode;
use App\Models\Anggota;
use App\Models\ShuKomponen;
use App\Models\ShuPeriode;
use App\Models\User;
use App\ShuCalculator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminShuCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function period(array $changes = []): ShuPeriode
    {
        return app(AdminShu::class)->save(array_replace(AdminShuParametersTest::parameters(), $changes));
    }

    private function saving(Anggota $member, string $date, int $amount, string $type = 'pokok', string $direction = 'setor'): void
    {
        $member->simpananTransaksis()->create(['tanggal' => $date, 'jenis' => $type, 'tipe' => $direction, 'nominal' => $amount, 'sumber' => 'manual']);
    }

    private function amount(ShuPeriode $period, Anggota $member, string $code): int
    {
        return (int) $period->shuAnggotas()->where('anggota_id', $member->id)->where('kode_komponen', $code)->value('shu');
    }

    public function test_largest_remainder_is_exact_deterministic_and_overflow_safe(): void
    {
        $service = app(ShuCalculator::class);
        $this->assertSame([1 => 34, 2 => 33, 3 => 33], $service->allocate(100, [1 => 1, 2 => 1, 3 => 1]));
        $this->assertSame(1000000000000, array_sum($service->allocate(1000000000000, [1 => 999999999999, 2 => 888888888888])));
        $this->assertSame([1 => 0, 2 => 0], $service->allocate(100, [1 => 0, 2 => 0]));
    }

    public function test_pools_membership_months_zero_denominators_and_recalculation(): void
    {
        $a = Anggota::factory()->create(['tanggal_masuk' => '2030-01-01']);
        $b = Anggota::factory()->create(['tanggal_masuk' => '2030-08-01']);
        $inactive = Anggota::factory()->create(['tanggal_masuk' => '2020-01-01', 'tanggal_keluar' => '2029-12-31', 'status' => 'keluar']);
        foreach ([$a, $b] as $m) {
            $this->saving($m, '2030-08-01', 100);
            $this->saving($m, '2030-08-01', 100, 'sukarela');
        }
        $p = $this->period(['pool_toko' => 101, 'pool_jasa_umum' => 170]);
        $service = app(ShuCalculator::class);
        $p = $service->calculate($p);
        $this->assertSame(61, (int) $p->shuAnggotas()->where('kode_komponen', 'simpanan_pokok_wajib')->sum('shu'));
        $this->assertSame(70, (int) $p->shuAnggotas()->where('kode_komponen', 'simpanan_sukarela')->sum('shu'));
        $this->assertSame(120, $this->amount($p, $a, 'jasa_umum'));
        $this->assertSame(50, $this->amount($p, $b, 'jasa_umum'));
        $this->assertSame(0, $p->shuAnggotas()->where('anggota_id', $inactive->id)->count());
        $this->assertStringContainsString('pembagi nol', implode(' ', $p->parameter['report']['warnings']));
        $before = $p->shuAnggotas()->orderBy('anggota_id')->orderBy('kode_komponen')->pluck('shu')->all();
        $service->calculate($p);
        $this->assertSame($before, $p->shuAnggotas()->orderBy('anggota_id')->orderBy('kode_komponen')->pluck('shu')->all());
        $this->assertSame(12, $p->shuAnggotas()->count());
        $changed = app(AdminShu::class)->save(array_replace(AdminShuParametersTest::parameters(), ['pool_toko' => 999]), $p);
        $this->assertSame(0, $changed->shuAnggotas()->count());
        $this->assertNull($changed->shu_bersih);
        $this->assertArrayNotHasKey('report', $changed->parameter);
    }

    public function test_average_month_end_balances_preserve_fractional_weights_and_subtract_withdrawals(): void
    {
        $a = Anggota::factory()->create();
        $b = Anggota::factory()->create();
        $this->saving($a, '2030-01-01', 120);
        $this->saving($a, '2030-12-01', 20, 'pokok', 'tarik');
        $this->saving($b, '2030-12-01', 100);
        $p = $this->period(['pool_toko' => 240, 'toko_simpanan' => 100, 'toko_belanja' => 0]);
        $service = app(ShuCalculator::class);
        $service->calculate($p);
        $this->assertSame(120, $this->amount($p, $a, 'simpanan_pokok_wajib'));
        $p = app(AdminShu::class)->save(array_replace(AdminShuParametersTest::parameters(), ['pool_toko' => 240, 'toko_simpanan' => 100, 'toko_belanja' => 0, 'dasar_saldo' => 'rata_bulanan']), $p);
        $service->calculate($p);
        // Weights: 11*120+100=1420 versus 100; 240*1420/1520 rounds to 224.
        $this->assertSame(224, $this->amount($p, $a, 'simpanan_pokok_wajib'));
        $this->assertSame(16, $this->amount($p, $b, 'simpanan_pokok_wajib'));
    }

    public function test_orders_use_completion_year_and_only_paid_interest_of_matching_loan_types(): void
    {
        $a = Anggota::factory()->create();
        $b = Anggota::factory()->create();
        foreach ([[$a, 100, '2030-12-31 12:00:00', 'selesai'], [$b, 900, '2031-01-01 00:00:00', 'selesai'], [$b, 500, '2030-06-01 12:00:00', 'baru']] as $i => [$m,$total,$date,$status]) {
            $m->pesanans()->create(['kode' => 'PSN-UJI-'.$i, 'tanggal' => '2030-01-01', 'selesai_pada' => $date, 'status' => $status, 'total' => $total]);
        }
        foreach (['usipa', 'usipa_khusus', 'toko'] as $i => $code) {
            $type = DB::table('pinjaman_jenis')->insertGetId(['kode' => $code, 'nama' => $code, 'bunga_persen_per_bulan' => 1, 'plafon_maks' => 100000, 'tenor_maks' => 12, 'butuh_dokumen_akad' => false]);
            $loan = $a->pinjamans()->create(['pinjaman_jenis_id' => $type, 'kode' => 'P-'.$i, 'jumlah' => 1000, 'tenor_bulan' => 3, 'bunga_persen_per_bulan' => 1, 'metode_bayar' => 'potong_gaji', 'status' => 'berjalan', 'tanggal_pengajuan' => '2030-01-01']);
            foreach ([['lunas', '2030-02-01', 100], ['belum', null, 900], ['lunas', '2031-01-01', 800]] as $j => [$status,$paid,$interest]) {
                $loan->angsurans()->create(['angsuran_ke' => $j + 1, 'jatuh_tempo' => '2030-02-01', 'pokok' => 100, 'bunga' => $interest, 'total' => 100 + $interest, 'status' => $status, 'tanggal_bayar' => $paid]);
            }
        }
        $p = app(ShuCalculator::class)->calculate($this->period(['pool_usipa' => 10, 'usipa_jasa' => 100, 'usipa_sukarela' => 0]));
        $this->assertSame(40, $this->amount($p, $a, 'belanja'));
        $this->assertSame(0, $this->amount($p, $b, 'belanja'));
        $this->assertSame(28, $this->amount($p, $a, 'jasa_usipa_anggota'));
        $this->assertSame(100, $this->amount($p, $a, 'jasa_usipa_khusus'));
        $this->assertStringContainsString('melebihi porsi', implode(' ', $p->parameter['report']['warnings']));
    }

    public function test_preview_search_totals_and_csv_use_persisted_results(): void
    {
        $a = Anggota::factory()->create(['nama' => 'Anggota Pratinjau']);
        $b = Anggota::factory()->create(['nama' => 'Anggota Lain']);
        $this->saving($a, '2030-01-01', 100);
        $p = app(ShuCalculator::class)->calculate($this->period());
        $this->get('/admin/shu/'.$p->id)->assertOk()->assertSee('Ringkasan pembagian')->assertSee('Anggota Pratinjau');
        Livewire::test(PreviewShuPeriode::class, ['record' => $p->id])->assertCanSeeTableRecords([$a, $b])->searchTable('Anggota Pratinjau')->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b]);
        $page = Livewire::test(PreviewShuPeriode::class, ['record' => $p->id]);
        $page->callAction('csv')->assertFileDownloaded('shu-2030.csv');
        $csv = $page->instance()->downloadCsv()->getCallback();
        ob_start();
        $csv();
        $content = ob_get_clean();
        $this->assertStringContainsString('Total keseluruhan', $content);
        $this->assertStringContainsString('Anggota Pratinjau', $content);
        $this->assertStringContainsString((string) $p->shu_bersih, $content);
    }

    public function test_calculation_is_atomic_on_failed_result_insert(): void
    {
        Anggota::factory()->create();
        $p = app(ShuCalculator::class)->calculate($this->period());
        $before = $p->shuAnggotas()->pluck('shu')->all();
        $created = 0;
        ShuKomponen::creating(function () use (&$created): void {
            if (++$created === 2) {
                throw new \RuntimeException('forced failure after first allocation');
            }
        });
        try {
            app(ShuCalculator::class)->calculate($p);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure after first allocation', $e->getMessage());
            $this->assertSame($before, $p->shuAnggotas()->pluck('shu')->all());
            $this->assertSame(6, $p->komponens()->count());
        } finally {
            ShuKomponen::flushEventListeners();
        }
    }
}
