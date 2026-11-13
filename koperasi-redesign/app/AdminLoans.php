<?php

namespace App;

use App\Models\Pinjaman;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminLoans
{
    public function __construct(private LoanSimulator $simulator) {}

    private function authorize(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    private function requireStatus(Pinjaman $loan, string $expected): void
    {
        if ($loan->status !== $expected) {
            throw ValidationException::withMessages(['status' => 'Status pinjaman sudah berubah atau transisi tidak diizinkan.']);
        }
    }

    public function approve(Pinjaman $record): Pinjaman
    {
        $this->authorize();
        // Aturan sementara: setujui dan cairkan dipisahkan; konfirmasi ke pengurus.
        DB::transaction(function () use ($record): void {
            $loan = Pinjaman::query()->lockForUpdate()->findOrFail($record->id);
            $this->requireStatus($loan, 'diajukan');
            $loan->update(['status' => 'disetujui', 'tanggal_disetujui' => today()]);
        });

        return $record->refresh();
    }

    public function reject(Pinjaman $record, string $note): Pinjaman
    {
        $this->authorize();
        $data = Validator::make(['catatan_admin' => trim($note)], ['catatan_admin' => ['required', 'string', 'max:10000']], [
            'catatan_admin.required' => 'Catatan penolakan wajib diisi.',
            'catatan_admin.max' => 'Catatan maksimal 10000 karakter.',
        ])->validate();
        DB::transaction(function () use ($record, $data): void {
            $loan = Pinjaman::query()->lockForUpdate()->findOrFail($record->id);
            $this->requireStatus($loan, 'diajukan');
            $loan->update(['status' => 'ditolak', 'catatan_admin' => $data['catatan_admin']]);
        });

        return $record->refresh();
    }

    public function disburse(Pinjaman $record): Pinjaman
    {
        $this->authorize();
        DB::transaction(function () use ($record): void {
            $loan = Pinjaman::query()->lockForUpdate()->findOrFail($record->id);
            $this->requireStatus($loan, 'disetujui');
            if ($loan->angsurans()->exists()) {
                throw ValidationException::withMessages(['status' => 'Jadwal angsuran sudah tersedia.']);
            }
            $schedule = $this->simulator->schedule($loan->jumlah, $loan->tenor_bulan, $loan->bunga_persen_per_bulan, CarbonImmutable::today());
            $loan->angsurans()->createMany($schedule);
            $loan->update(['status' => 'berjalan']);
        });

        return $record->refresh();
    }

    /** @param array<int, int|string> $ids */
    public function markPaid(Pinjaman $record, array $ids, string $paidDate): Pinjaman
    {
        $this->authorize();
        $data = Validator::make(['ids' => $ids, 'tanggal_bayar' => $paidDate], [
            'ids' => ['required', 'array', 'min:1'], 'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'tanggal_bayar' => ['required', 'date_format:Y-m-d'],
        ], ['tanggal_bayar.date_format' => 'Tanggal bayar tidak valid.', 'ids.required' => 'Pilih angsuran yang akan dilunasi.'])->validate();
        DB::transaction(function () use ($record, $data): void {
            $loan = Pinjaman::query()->lockForUpdate()->findOrFail($record->id);
            $this->requireStatus($loan, 'berjalan');
            $installments = $loan->angsurans()->whereKey($data['ids'])->lockForUpdate()->get();
            if ($installments->count() !== count($data['ids']) || $installments->contains('status', 'lunas')) {
                throw ValidationException::withMessages(['ids' => 'Angsuran tidak sesuai pinjaman atau sudah lunas.']);
            }
            $loan->angsurans()->whereKey($data['ids'])->update(['status' => 'lunas', 'tanggal_bayar' => $data['tanggal_bayar']]);
            if ($loan->angsurans()->count() === $loan->tenor_bulan && ! $loan->angsurans()->where('status', '!=', 'lunas')->exists()) {
                $loan->update(['status' => 'lunas']);
            }
        });

        return $record->refresh();
    }
}
