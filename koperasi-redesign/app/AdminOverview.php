<?php

namespace App;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\SimpananTransaksi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminOverview
{
    /** @return array{active_members: int, savings: int, running_loans: int, pending_loans: int, new_orders: int} */
    public function totals(): array
    {
        return [
            'active_members' => Anggota::where('status', 'aktif')->count(),
            'savings' => (int) SimpananTransaksi::query()->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END), 0) AS saldo")->value('saldo'),
            'running_loans' => Pinjaman::where('status', 'berjalan')->count(),
            'pending_loans' => Pinjaman::where('status', 'diajukan')->count(),
            'new_orders' => Pesanan::where('status', 'baru')->count(),
        ];
    }

    /** @return array<string, Collection> */
    public function actions(): array
    {
        return [
            'loans' => Pinjaman::query()->with('anggota:id,nama')->where('status', 'diajukan')->orderBy('tanggal_pengajuan')->orderBy('id')->limit(5)->get(),
            'orders' => Pesanan::query()->with('anggota:id,nama')->where('status', 'baru')->orderBy('tanggal')->orderBy('id')->limit(5)->get(),
            'installments' => Angsuran::query()->with('pinjaman.anggota:id,nama')->where('status', '!=', 'lunas')->where('jatuh_tempo', '<', today()->toDateString())->orderBy('jatuh_tempo')->orderBy('id')->limit(5)->get(),
        ];
    }

    /** @return array<int, int> */
    public function savingsYears(): array
    {
        return $this->yearsFor('simpanan_transaksis', 'tanggal');
    }

    /** @return array<int, int> */
    public function orderYears(): array
    {
        return $this->yearsFor('pesanans', 'tanggal');
    }

    /** @return array{labels: array<int, string>, setor: array<int, int>, tarik: array<int, int>, hasData: bool} */
    public function monthlySavings(int $year): array
    {
        $monthExpression = $this->monthExpression('tanggal');
        $rows = DB::table('simpanan_transaksis')
            ->where('tanggal', '>=', sprintf('%04d-01-01', $year))
            ->where('tanggal', '<', sprintf('%04d-01-01', $year + 1))
            ->selectRaw($monthExpression.' AS month')
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN nominal ELSE 0 END) AS setor, SUM(CASE WHEN tipe = 'tarik' THEN nominal ELSE 0 END) AS tarik")
            ->groupByRaw($monthExpression)
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->month);
        $months = range(1, 12);

        return [
            'labels' => array_map(fn (int $month): string => CarbonImmutable::create(2020, $month, 1)->locale('id')->translatedFormat('M'), $months),
            'setor' => array_map(fn (int $month): int => (int) ($rows->get($month)?->setor ?? 0), $months),
            'tarik' => array_map(fn (int $month): int => (int) ($rows->get($month)?->tarik ?? 0), $months),
            'hasData' => $rows->isNotEmpty(),
        ];
    }

    /** @return array{labels: array<int, string>, counts: array<int, int>, hasData: bool} */
    public function monthlyOrders(int $year): array
    {
        $monthExpression = $this->monthExpression('tanggal');
        $rows = DB::table('pesanans')
            ->where('tanggal', '>=', sprintf('%04d-01-01', $year))
            ->where('tanggal', '<', sprintf('%04d-01-01', $year + 1))
            ->selectRaw($monthExpression.' AS month, COUNT(*) AS total')
            ->groupByRaw($monthExpression)
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->month);
        $months = range(1, 12);

        return [
            'labels' => array_map(fn (int $month): string => CarbonImmutable::create(2020, $month, 1)->locale('id')->translatedFormat('M'), $months),
            'counts' => array_map(fn (int $month): int => (int) ($rows->get($month)?->total ?? 0), $months),
            'hasData' => $rows->isNotEmpty(),
        ];
    }

    /** @return array{labels: array<int, string>, counts: array<int, int>} */
    public function loanStatuses(): array
    {
        $counts = Pinjaman::query()->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return ['labels' => array_map('ucfirst', Pinjaman::STATUSES), 'counts' => array_map(fn (string $status): int => (int) ($counts[$status] ?? 0), Pinjaman::STATUSES)];
    }

    /** @return array<int, int> */
    private function yearsFor(string $table, string $column): array
    {
        $yearExpression = $this->yearExpression($column);

        return DB::table($table)
            ->selectRaw($yearExpression.' AS year')
            ->groupByRaw($yearExpression)
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn (mixed $year): int => (int) $year)
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', $column) AS INTEGER)" : "MONTH($column)";
    }

    private function yearExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite' ? "CAST(strftime('%Y', $column) AS INTEGER)" : "YEAR($column)";
    }
}
