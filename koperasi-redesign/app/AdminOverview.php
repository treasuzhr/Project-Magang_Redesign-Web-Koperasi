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

    /** @return array{labels: array<int, string>, setor: array<int, int>, tarik: array<int, int>} */
    public function monthlySavings(): array
    {
        $months = $this->months();
        $rows = DB::table('simpanan_transaksis')->where('tanggal', '>=', $months[0]->toDateString())->where('tanggal', '<', today()->addDay()->toDateString())
            ->selectRaw($this->monthExpression('tanggal').' AS month')
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN nominal ELSE 0 END) AS setor, SUM(CASE WHEN tipe = 'tarik' THEN nominal ELSE 0 END) AS tarik")
            ->groupByRaw($this->monthExpression('tanggal'))->get()->keyBy('month');

        return [
            'labels' => array_map(fn (CarbonImmutable $month): string => $month->locale('id')->translatedFormat('M Y'), $months),
            'setor' => array_map(fn (CarbonImmutable $month): int => (int) ($rows->get($month->format('Y-m'))?->setor ?? 0), $months),
            'tarik' => array_map(fn (CarbonImmutable $month): int => (int) ($rows->get($month->format('Y-m'))?->tarik ?? 0), $months),
        ];
    }

    /** @return array{labels: array<int, string>, counts: array<int, int>} */
    public function monthlyOrders(): array
    {
        $months = $this->months();
        $rows = DB::table('pesanans')->whereBetween('tanggal', [$months[0]->startOfDay()->toDateTimeString(), now()->toDateTimeString()])
            ->selectRaw($this->monthExpression('tanggal').' AS month, COUNT(*) AS total')
            ->groupByRaw($this->monthExpression('tanggal'))->pluck('total', 'month');

        return [
            'labels' => array_map(fn (CarbonImmutable $month): string => $month->locale('id')->translatedFormat('M Y'), $months),
            'counts' => array_map(fn (CarbonImmutable $month): int => (int) ($rows[$month->format('Y-m')] ?? 0), $months),
        ];
    }

    /** @return array{labels: array<int, string>, counts: array<int, int>} */
    public function loanStatuses(): array
    {
        $counts = Pinjaman::query()->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return ['labels' => array_map('ucfirst', Pinjaman::STATUSES), 'counts' => array_map(fn (string $status): int => (int) ($counts[$status] ?? 0), Pinjaman::STATUSES)];
    }

    /** @return array<int, CarbonImmutable> */
    private function months(): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonths(11);

        return array_map(fn (int $offset): CarbonImmutable => $start->addMonths($offset), range(0, 11));
    }

    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', $column)" : "DATE_FORMAT($column, '%Y-%m')";
    }
}
