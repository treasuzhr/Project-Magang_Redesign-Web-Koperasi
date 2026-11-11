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

    /** @return Builder<Pinjaman> */
    private function loanQuery(Anggota $anggota): Builder
    {
        return Pinjaman::query()->where('anggota_id', $anggota->id)->with('jenis')
            ->withSum(['angsurans as paid_principal' => fn (Builder $query) => $query->where('status', 'lunas')], 'pokok')
            ->withSum(['angsurans as paid_total' => fn (Builder $query) => $query->where('status', 'lunas')], 'total')
            ->withCount(['angsurans as unpaid_count' => fn (Builder $query) => $query->where('status', '!=', 'lunas')])
            ->withCount(['angsurans as overdue_count' => fn (Builder $query) => $query->where('status', '!=', 'lunas')->where('jatuh_tempo', '<', today()->toDateString())]);
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function loanList(Anggota $anggota, ?string $status = null, ?string $jenis = null, int $perPage = 10): LengthAwarePaginator
    {
        $paginator = $this->loanQuery($anggota)
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($jenis !== null, fn (Builder $query) => $query->whereHas('jenis', fn (Builder $kind) => $kind->where('kode', $jenis)))
            ->orderByDesc('tanggal_pengajuan')->orderByDesc('id')->paginate($perPage)->withQueryString();
        $first = $paginator->firstItem() ?? 0;

        return $paginator->through(fn (Pinjaman $loan, int $index): array => array_merge($this->loanRow($loan), ['number' => $first + $index]));
    }

    /** @return array<string, mixed> */
    private function loanRow(Pinjaman $loan): array
    {
        return [
            'id' => $loan->id, 'application_number' => $loan->kode,
            'loan_type' => match ($loan->jenis->kode) {
                'toko' => 'Toko', 'usipa' => 'Unit Simpan Pinjam', 'usipa_khusus' => 'USP Khusus',
            },
            'total' => $loan->jumlah, 'term' => $loan->tenor_bulan,
            'interest_rate' => $loan->bunga_persen_per_bulan,
            'payment_method' => $loan->metode_bayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunjangan Kinerja',
            'remaining' => max(0, $loan->jumlah - (int) $loan->paid_principal),
            'paid_total' => (int) $loan->paid_total, 'remaining_installments' => (int) $loan->unpaid_count,
            'overdue_count' => (int) $loan->overdue_count, 'status' => ucfirst($loan->status),
            'date' => $loan->tanggal_pengajuan->toDateString(),
            'approved_date' => $loan->tanggal_disetujui?->locale('id')->translatedFormat('j F Y') ?? '-',
            'needs_akad' => $loan->jenis->butuh_dokumen_akad,
            'action_url' => route('anggota.pinjaman.detail', ['id' => $loan->id]),
            'action_aria_label' => 'Lihat detail pengajuan '.$loan->kode,
        ];
    }

    /** @return array<string, mixed> */
    public function loanDetail(Anggota $anggota, int $id): array
    {
        $loan = $this->loanQuery($anggota)->with(['angsurans' => fn (HasMany $query) => $query->orderBy('angsuran_ke')->orderBy('id')])->findOrFail($id);
        $result = $this->loanRow($loan);
        $result['schedule'] = $loan->angsurans->map(fn ($installment): array => [
            'number' => $installment->angsuran_ke,
            'due_date' => $installment->jatuh_tempo->locale('id')->translatedFormat('j F Y'),
            'principal' => $installment->pokok, 'interest' => $installment->bunga, 'total' => $installment->total,
            'status' => $installment->status === 'lunas' ? 'Lunas' : ($installment->jatuh_tempo->lt(today()) ? 'Tunggak' : 'Belum'),
            'paid_date' => $installment->tanggal_bayar?->locale('id')->translatedFormat('j F Y') ?? '-',
        ])->all();
        $result['akad_available'] = $loan->dokumen_akad_path !== null && Storage::disk('local')->exists($loan->dokumen_akad_path);

        return $result;
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
