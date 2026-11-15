<?php

namespace App;

use App\Models\Anggota;
use App\Models\ShuPeriode;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShuCalculator
{
    public const LABELS = ['simpanan_pokok_wajib' => 'Simpanan Pokok+Wajib', 'belanja' => 'Belanja', 'simpanan_sukarela' => 'Simpanan Sukarela', 'jasa_usipa_anggota' => 'Jasa Sipa Angg', 'jasa_usipa_khusus' => 'Jasa Sipa Sus', 'jasa_umum' => 'Jasa Umum'];

    /**
     * Exact integer allocation; ties use ascending member ID for repeatability.
     *
     * @param  array<int|string, int>  $weights
     * @return array<int|string, int>
     */
    public function allocate(int $pool, array $weights): array
    {
        $sum = BigInteger::zero();
        foreach ($weights as $weight) {
            if ($weight < 0) {
                throw new \InvalidArgumentException('Dasar pembagian tidak boleh negatif.');
            }
            $sum = $sum->plus($weight);
        }
        $shares = array_fill_keys(array_keys($weights), 0);
        if ($pool < 0) {
            throw new \InvalidArgumentException('Pool tidak boleh negatif.');
        }
        if ($sum->isZero()) {
            return $shares;
        }
        $remainders = [];
        foreach ($weights as $id => $weight) {
            [$q,$r] = BigInteger::of($pool)->multipliedBy($weight)->quotientAndRemainder($sum);
            $shares[$id] = $q->toInt();
            $remainders[$id] = $r;
        }
        $ids = array_keys($weights);
        usort($ids, fn (int|string $a, int|string $b): int => $remainders[$b]->compareTo($remainders[$a]) ?: ($a <=> $b));
        foreach (array_slice($ids, 0, $pool - array_sum($shares)) as $id) {
            $shares[$id]++;
        }

        return $shares;
    }

    public function calculate(ShuPeriode $period): ShuPeriode
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return DB::transaction(function () use ($period): ShuPeriode {
            $period = ShuPeriode::query()->lockForUpdate()->findOrFail($period->id);
            if ($period->status !== 'draft' || ! isset($period->parameter['pool_toko'])) {
                throw ValidationException::withMessages(['periode' => 'Hanya draf dengan parameter lengkap yang dapat dihitung.']);
            }
            $p = $period->parameter;
            $year = $period->tahun;
            $start = "$year-01-01";
            $end = "$year-12-31";
            $warnings = [];
            $members = Anggota::query()->where('tanggal_masuk', '<=', $end)->where(fn (Builder $q) => $q->whereNull('tanggal_keluar')->orWhere('tanggal_keluar', '>=', $start))
                ->where(fn (Builder $q) => $q->where('status', 'aktif')->orWhereNotNull('tanggal_keluar'))->orderBy('id')->get();
            $missing = Anggota::query()->where('status', '!=', 'aktif')->whereNull('tanggal_keluar')->where('tanggal_masuk', '<=', $end)->count();
            if ($missing) {
                $warnings[] = "$missing anggota nonaktif tanpa tanggal keluar tidak diikutkan; lengkapi tanggal keluar.";
            }
            $ids = $members->modelKeys();
            $bases = array_fill_keys(array_keys(self::LABELS), array_fill_keys($ids, 0));
            $months = [];
            foreach ($members as $member) {
                $from = $member->tanggal_masuk->max(CarbonImmutable::parse($start));
                $until = ($member->tanggal_keluar ?? CarbonImmutable::parse($end))->min(CarbonImmutable::parse($end));
                // Konvensi bulan kalender masuk/keluar ikut dihitung; konfirmasi ke pengurus.
                $months[$member->id] = ($until->year - $from->year) * 12 + $until->month - $from->month + 1;
            }
            $average = $p['dasar_saldo'] === 'rata_bulanan';
            $savingsWeights = array_fill_keys($ids, ['pokok' => 0, 'wajib' => 0, 'sukarela' => 0]);
            $savings = DB::table('simpanan_transaksis')->whereIn('anggota_id', $ids)->whereIn('jenis', ['pokok', 'wajib', 'sukarela'])->where('tanggal', '<=', $end)->select('anggota_id', 'jenis');
            // Persentase dan dasar simpanan merupakan parameter sementara: konfirmasi ke pengurus.
            if ($average) {
                for ($month = 1; $month <= 12; $month++) {
                    $date = CarbonImmutable::create($year, $month, 1)->endOfMonth()->toDateString();
                    $savings->selectRaw("SUM(CASE WHEN tanggal <= ? THEN CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END ELSE 0 END) AS m$month", [$date]);
                }
            } else {
                $savings->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END) AS saldo");
            }
            foreach ($savings->groupBy('anggota_id', 'jenis')->get() as $row) {
                $weight = $average ? array_sum(array_map(fn (int $m): int => (int) $row->{'m'.$m}, range(1, 12))) : (int) $row->saldo;
                if ($weight < 0) {
                    throw ValidationException::withMessages(['periode' => 'Saldo simpanan negatif ditemukan. Periksa transaksi sebelum menghitung SHU.']);
                }
                $code = $row->jenis === 'sukarela' ? 'simpanan_sukarela' : 'simpanan_pokok_wajib';
                $bases[$code][$row->anggota_id] += $weight;
                $savingsWeights[$row->anggota_id][$row->jenis] = $weight;
            }
            $savingsBreakdown = [];
            foreach ($savingsWeights as $id => $values) {
                $combined = $bases['simpanan_pokok_wajib'][$id];
                $rounded = $average ? BigInteger::of($combined)->dividedBy(12, RoundingMode::HalfUp)->toInt() : $combined;
                // Preserve the displayed Pokok/Wajib split and its exact combined integer basis.
                $savingsBreakdown[$id] = $this->allocate($rounded, ['pokok' => $values['pokok'], 'wajib' => $values['wajib']]);
            }
            $spending = DB::table('pesanans')->whereIn('anggota_id', $ids)->where('status', 'selesai')->whereBetween('selesai_pada', [$start.' 00:00:00', $end.' 23:59:59'])->select('anggota_id')->selectRaw('SUM(total) AS amount')->groupBy('anggota_id')->get();
            foreach ($spending as $row) {
                $bases['belanja'][$row->anggota_id] = (int) $row->amount;
            }
            $interest = DB::table('angsurans')->join('pinjamans', 'pinjamans.id', '=', 'angsurans.pinjaman_id')->join('pinjaman_jenis', 'pinjaman_jenis.id', '=', 'pinjamans.pinjaman_jenis_id')->whereIn('pinjamans.anggota_id', $ids)->whereIn('pinjaman_jenis.kode', ['usipa', 'usipa_khusus'])->where('angsurans.status', 'lunas')->whereBetween('angsurans.tanggal_bayar', [$start, $end])->select('pinjamans.anggota_id', 'pinjaman_jenis.kode')->selectRaw('SUM(angsurans.bunga) AS amount')->groupBy('pinjamans.anggota_id', 'pinjaman_jenis.kode')->get();
            foreach ($interest as $row) {
                $bases[$row->kode === 'usipa' ? 'jasa_usipa_anggota' : 'jasa_usipa_khusus'][$row->anggota_id] = (int) $row->amount;
            }
            $bases['jasa_umum'] = $months;
            $toko = $this->allocate((int) $p['pool_toko'], ['simpanan_pokok_wajib' => $this->percentUnits($p['toko_simpanan']), 'belanja' => $this->percentUnits($p['toko_belanja'])]);
            $usipa = $this->allocate((int) $p['pool_usipa'], ['simpanan_sukarela' => $this->percentUnits($p['usipa_sukarela']), 'jasa_usipa_anggota' => $this->percentUnits($p['usipa_jasa'])]);
            $pools = $toko + $usipa + ['jasa_usipa_khusus' => (int) $p['pool_usipa_khusus'], 'jasa_umum' => (int) $p['pool_jasa_umum']];
            $period->shuAnggotas()->delete();
            $period->komponens()->delete();
            $distributed = [];
            $fixedUnits = $this->percentUnits($p['jasa_tetap']);
            foreach (self::LABELS as $code => $label) {
                $fixed = $code === 'jasa_usipa_anggota';
                $weights = $bases[$code];
                // Fixed interest payout is NOT capped/scaled to its pool. Round the aggregate
                // percentage to rupiah, then allocate its fractions using largest remainders.
                $fixedTotal = $fixed ? $this->weightTotal($weights)->multipliedBy($fixedUnits)->dividedBy(1000000, RoundingMode::HalfUp)->toInt() : 0;
                $amount = $fixed ? $fixedTotal : $pools[$code];
                $shares = $fixed ? $this->fixedShares($weights, $fixedUnits, $fixedTotal) : $this->allocate($amount, $weights);
                $distributed[$code] = array_sum($shares);
                if ($this->weightTotal($weights)->isZero()) {
                    $warnings[] = "$label: pembagi nol; tidak ada pembagian.";
                }
                if ($fixed && $distributed[$code] > $pools[$code]) {
                    $warnings[] = 'Jasa Sipa Angg melebihi porsi pool yang disediakan.';
                }
                $unit = match ($code) {
                    'simpanan_pokok_wajib','belanja' => 'toko','simpanan_sukarela','jasa_usipa_anggota' => 'usipa','jasa_usipa_khusus' => 'usipa_khusus',default => 'jasa_umum'
                };
                $component = $period->komponens()->create(['kode' => $code, 'unit' => $unit, 'metode' => $fixed ? 'persentase_tetap' : ($code === 'jasa_umum' ? 'bagi_rata_prorata' : 'proporsional'), 'pool_nominal' => $pools[$code], 'persentase' => $fixed ? $p['jasa_tetap'] : null]);
                $rows = [];
                foreach ($shares as $id => $share) {
                    $base = $weights[$id];
                    if ($average && in_array($code, ['simpanan_pokok_wajib', 'simpanan_sukarela'], true)) {
                        $base = BigInteger::of($base)->dividedBy(12, RoundingMode::HalfUp)->toInt();
                    }
                    $rows[] = ['shu_periode_id' => $period->id, 'shu_komponen_id' => $component->id, 'anggota_id' => $id, 'kode_komponen' => $code, 'jumlah_dasar' => $base, 'bulan_aktif' => $months[$id], 'shu' => $share, 'created_at' => now(), 'updated_at' => now()];
                }
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('shu_anggotas')->insert($chunk);
                }
            }
            $noData = [];
            foreach ($members as $member) {
                $hasData = false;
                foreach (array_diff(array_keys(self::LABELS), ['jasa_umum']) as $code) {
                    $hasData = $hasData || $bases[$code][$member->id] > 0;
                }
                if (! $hasData) {
                    $noData[] = $member->id;
                }
            }
            if ($noData) {
                $warnings[] = count($noData).' anggota tanpa data simpanan, belanja, atau bunga dibayar; hanya memperoleh Jasa Umum.';
            }
            $report = ['calculated_at' => now()->toIso8601String(), 'warnings' => $warnings, 'members' => $members->count(), 'without_data' => $noData, 'savings_breakdown' => $savingsBreakdown, 'component_pools' => $pools, 'distributed' => $distributed];
            $total = BigInteger::zero();
            foreach ($distributed as $value) {
                $total = $total->plus($value);
            }
            if ($total->isGreaterThan(PHP_INT_MAX)) {
                throw ValidationException::withMessages(['periode' => 'Total SHU melampaui kapasitas bilangan rupiah.']);
            }
            $period->update(['parameter' => array_merge($p, ['report' => $report]), 'shu_bersih' => $total->toInt()]);

            return $period->refresh();
        });
    }

    /**
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    private function fixedShares(array $weights, int $units, int $target): array
    {
        $shares = [];
        $remainders = [];
        foreach ($weights as $id => $weight) {
            [$q,$r] = BigInteger::of($weight)->multipliedBy($units)->quotientAndRemainder(1000000);
            $shares[$id] = $q->toInt();
            $remainders[$id] = $r;
        }
        $ids = array_keys($weights);
        usort($ids, fn (int $a, int $b): int => $remainders[$b]->compareTo($remainders[$a]) ?: ($a <=> $b));
        foreach (array_slice($ids, 0, $target - array_sum($shares)) as $id) {
            $shares[$id]++;
        }

        return $shares;
    }

    /** @param array<int|string, int> $weights */
    private function weightTotal(array $weights): BigInteger
    {
        $total = BigInteger::zero();
        foreach ($weights as $weight) {
            $total = $total->plus($weight);
        }

        return $total;
    }

    private function percentUnits(int|float|string $value): int
    {
        return BigInteger::of((string) BigDecimal::of((string) $value)->multipliedBy(10000)->toScale(0))->toInt();
    }

    public function previewQuery(ShuPeriode $period): Builder
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $totals = DB::table('shu_anggotas')->where('shu_periode_id', $period->id)->select('anggota_id')->selectRaw('SUM(shu) AS total');
        foreach (self::LABELS as $code => $label) {
            $totals->selectRaw("SUM(CASE WHEN kode_komponen = ? THEN shu ELSE 0 END) AS $code", [$code]);
        }
        $totals->groupBy('anggota_id');

        return Anggota::query()->joinSub($totals, 'hasil', fn ($join) => $join->on('hasil.anggota_id', '=', 'anggotas.id'))->join('users', 'users.id', '=', 'anggotas.user_id')->select('anggotas.*', 'users.nip', 'hasil.total', ...array_map(fn (string $code): string => 'hasil.'.$code, array_keys(self::LABELS)));
    }
}
