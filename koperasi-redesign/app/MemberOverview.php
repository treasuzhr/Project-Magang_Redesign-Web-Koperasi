<?php

namespace App;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\ShuPeriode;
use App\Models\SimpananTransaksi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemberOverview
{
    /** @return array{summary: array<string, int>, total: int} */
    public function savings(Anggota $anggota): array
    {
        $balances = $anggota->simpananTransaksis()->whereIn('jenis', SimpananTransaksi::JENIS)
            ->select('jenis')
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END) AS saldo")
            ->groupBy('jenis')->pluck('saldo', 'jenis');
        $summary = [];
        foreach (SimpananTransaksi::JENIS as $jenis) {
            $summary[$jenis] = (int) ($balances[$jenis] ?? 0);
        }

        return ['summary' => $summary, 'total' => array_sum($summary)];
    }

    /** @return array{name: string, nip: string, jabatan: string, avatar_url: ?string} */
    public function memberProfile(Anggota $anggota): array
    {
        $anggota->loadMissing('user');

        return [
            'name' => $anggota->nama, 'nip' => $anggota->user->nip,
            'jabatan' => $anggota->jabatan ?: 'Belum diisi',
            'avatar_url' => $anggota->avatar_path ? Storage::disk('public')->url($anggota->avatar_path) : null,
        ];
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function savingsHistory(Anggota $anggota, string $jenis, ?string $tipe = null, ?int $tahun = null, int $perPage = 10): LengthAwarePaginator
    {
        // Calculate balances before year/type filters, so opening balances never reset on a filtered page.
        $ledger = $anggota->simpananTransaksis()->select('simpanan_transaksis.*')
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END) OVER (PARTITION BY jenis ORDER BY tanggal, id ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS running_balance");
        $paginator = DB::query()->fromSub($ledger, 'ledger')->where('jenis', $jenis)
            ->when($tipe !== null, fn ($query) => $query->where('tipe', $tipe))
            ->when($tahun !== null, fn ($query) => $query->whereYear('tanggal', $tahun))
            ->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage)->withQueryString();
        $first = $paginator->firstItem() ?? 0;

        return $paginator->through(function ($row, int $index) use ($first): array {
            $date = Carbon::parse($row->tanggal)->locale('id');

            return [
                'id' => (int) $row->id, 'number' => $first + $index, 'jenis' => $row->jenis, 'type' => $row->tipe,
                'year' => $date->year, 'month' => $date->translatedFormat('F'), 'date' => $date->toDateString(),
                'nominal' => (int) $row->nominal, 'amount' => $row->tipe === 'tarik' ? -(int) $row->nominal : (int) $row->nominal,
                'running_balance' => (int) $row->running_balance,
            ];
        });
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function savingsYears(Anggota $anggota, string $jenis, ?string $tipe = null, ?int $tahun = null, int $perPage = 10): LengthAwarePaginator
    {
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', tanggal) AS INTEGER)" : 'YEAR(tanggal)';
        $paginator = $anggota->simpananTransaksis()->where('jenis', $jenis)
            ->when($tipe !== null, fn ($query) => $query->where('tipe', $tipe))
            ->when($tahun !== null, fn ($query) => $query->whereYear('tanggal', $tahun))
            ->selectRaw($yearExpression.' AS year')
            ->selectRaw("SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END) AS amount")
            ->selectRaw('MAX(tanggal) AS latest_date')->groupByRaw($yearExpression)
            ->orderByDesc('latest_date')->orderByDesc('year')->paginate($perPage)->withQueryString();
        $anggota->loadMissing('user');
        $nip = $anggota->user->nip;
        $first = $paginator->firstItem() ?? 0;

        return $paginator->through(fn ($row, int $index): array => [
            'number' => $first + $index, 'year' => (int) $row->year, 'amount' => (int) $row->amount, 'nip' => $nip,
        ]);
    }

    /** @return array{active_remaining: int, disbursed_total: int, pending_total: int} */
    public function loanSummary(Anggota $anggota): array
    {
        $loans = $anggota->pinjamans()->select(['id', 'status', 'jumlah'])
            ->withSum(['angsurans as paid_principal' => fn (Builder $query) => $query->where('status', 'lunas')], 'pokok')->get();

        return [
            'active_remaining' => (int) $loans->where('status', 'berjalan')->sum(fn (Pinjaman $loan): int => max(0, $loan->jumlah - (int) $loan->paid_principal)),
            // There is no disbursement ledger/date yet. These statuses indicate disbursed loans.
            'disbursed_total' => (int) $loans->whereIn('status', ['berjalan', 'lunas'])->sum('jumlah'),
            'pending_total' => (int) $loans->where('status', 'diajukan')->sum('jumlah'),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function activeLoans(Anggota $anggota): Collection
    {
        return $anggota->pinjamans()->where('status', 'berjalan')
            ->withSum(['angsurans as paid_principal' => fn (Builder $query) => $query->where('status', 'lunas')], 'pokok')
            ->with([
                'jenis',
                'angsurans' => fn (HasMany $query) => $query->whereIn('status', ['belum', 'tunggak'])
                    ->orderBy('jatuh_tempo')->orderBy('angsuran_ke'),
            ])->orderBy('tanggal_pengajuan')->orderBy('id')->get()
            ->map(function (Pinjaman $loan): array {
                $next = $loan->angsurans->first();

                return [
                    'id' => $loan->id, 'code' => $loan->kode, 'type' => $loan->jenis->kode,
                    'type_name' => $loan->jenis->nama, 'amount' => $loan->jumlah,
                    'remaining_principal' => max(0, $loan->jumlah - (int) $loan->paid_principal),
                    'next_installment' => $next === null ? null : [
                        'number' => $next->angsuran_ke, 'due_date' => $next->jatuh_tempo->toDateString(), 'amount' => $next->total,
                    ],
                    'overdue_count' => $loan->angsurans->where('status', 'tunggak')->count(),
                ];
            });
    }

    /** @return array{year: int, amount: int}|null */
    public function latestShu(Anggota $anggota): ?array
    {
        $periode = ShuPeriode::where('status', 'ditetapkan')->orderByDesc('tahun')->first();
        if ($periode === null) {
            return null;
        }
        $allocation = $anggota->shuAnggotas()->where('shu_periode_id', $periode->id)
            ->selectRaw('COUNT(*) AS allocation_count, COALESCE(SUM(shu), 0) AS total_shu')->first();
        if ((int) $allocation->allocation_count === 0) {
            return null;
        }

        return ['year' => $periode->tahun, 'amount' => (int) $allocation->total_shu];
    }

    /** @return Collection<int, array{year: int, amount: int}> */
    public function shuHistory(Anggota $anggota): Collection
    {
        return $anggota->shuAnggotas()->join('shu_periodes', 'shu_periodes.id', '=', 'shu_anggotas.shu_periode_id')
            ->where('shu_periodes.status', 'ditetapkan')->select('shu_periodes.tahun')
            ->selectRaw('SUM(shu_anggotas.shu) AS total_shu')->groupBy('shu_periodes.tahun')
            ->orderByDesc('shu_periodes.tahun')->get()
            ->map(fn ($row): array => ['year' => (int) $row->tahun, 'amount' => (int) $row->total_shu]);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function recentOrders(Anggota $anggota, int $limit = 5): Collection
    {
        return $anggota->pesanans()->withCount('items')->orderByDesc('tanggal')->orderByDesc('id')
            ->limit(max(1, min(20, $limit)))->get()
            ->map(fn ($order): array => [
                'id' => $order->id, 'code' => $order->kode,
                'date' => $order->tanggal->locale('id')->translatedFormat('d F Y'),
                'status' => $order->status, 'total' => $order->total, 'item_count' => (int) $order->items_count,
            ]);
    }

    /** @return array<string, mixed> */
    public function dashboard(Anggota $anggota): array
    {
        $anggota->loadMissing('user');
        $savings = $this->savings($anggota);
        $summary = $savings['summary'];
        $totalSimpanan = $savings['total'];
        $loanSummary = $this->loanSummary($anggota);
        $member = [
            'name' => $anggota->nama, 'nip' => $anggota->user->nip,
            'jabatan' => $anggota->jabatan ?: 'Belum diisi',
            'avatar_url' => $anggota->avatar_path ? Storage::disk('public')->url($anggota->avatar_path) : null,
        ];
        $composition = [];
        foreach (['pokok' => '#4a9cc4', 'wajib' => '#2fa37a', 'sukarela' => '#7fad39', 'khusus' => '#f0c23b'] as $jenis => $color) {
            $composition[] = [
                'name' => ucfirst($jenis), 'amount' => $summary[$jenis],
                'percent' => $totalSimpanan > 0 ? round($summary[$jenis] / $totalSimpanan * 100, 1) : 0.0,
                'color' => $color,
            ];
        }
        $compositionLabel = 'Komposisi simpanan: '.collect($composition)
            ->map(fn (array $segment): string => strtolower($segment['name']).' '.number_format($segment['percent'], 1, ',', '.').' persen')->implode(', ').'.';
        $stats = [
            'simpanan' => collect($composition)->map(fn (array $segment): array => ['name' => 'Simpanan '.$segment['name'], 'amount' => $segment['amount']])->all(),
            'pinjaman' => [
                ['name' => 'Pinjaman Aktif', 'amount' => $loanSummary['active_remaining']],
                ['name' => 'Total Pinjaman Cair', 'amount' => $loanSummary['disbursed_total']],
                ['name' => 'Pinjaman Dalam Pengajuan', 'amount' => $loanSummary['pending_total']],
            ],
        ];

        return compact('member', 'summary', 'totalSimpanan', 'composition', 'compositionLabel', 'stats');
    }
}
