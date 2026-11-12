<?php

namespace App;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanApplication
{
    // konfirmasi ke pengurus: sementara hanya satu pengajuan diajukan per anggota dan jenis.
    public const ONE_PENDING_PER_KIND = true;

    public function __construct(private LoanSimulator $simulator) {}

    /** @param array{loan_type: string, loan_total: string|int, loan_term: string|int, payment_method: string} $data */
    public function submit(Anggota $anggota, array $data): Pinjaman
    {
        return DB::transaction(function () use ($anggota, $data): Pinjaman {
            // Lock the member row to serialize duplicate checks, including the first application.
            $member = Anggota::whereKey($anggota->id)->lockForUpdate()->firstOrFail();
            abort_unless($member->status === 'aktif', 403);
            $kind = PinjamanJenis::where('kode', $data['loan_type'])->lockForUpdate()->firstOrFail();
            if ((int) $data['loan_total'] > $kind->plafon_maks || (int) $data['loan_term'] > $kind->tenor_maks) {
                throw ValidationException::withMessages(['loan_type' => 'Ketentuan pinjaman berubah. Periksa kembali jumlah dan tenor.']);
            }
            if (self::ONE_PENDING_PER_KIND && $member->pinjamans()->where('pinjaman_jenis_id', $kind->id)->where('status', 'diajukan')->exists()) {
                throw ValidationException::withMessages(['loan_type' => 'Masih ada pengajuan untuk jenis pinjaman ini yang sedang diproses.']);
            }
            // Recalculate on submit; estimates are not stored and do not create installments.
            $this->simulator->calculate((int) $data['loan_total'], (int) $data['loan_term'], $kind->bunga_persen_per_bulan);

            return $member->pinjamans()->create([
                'pinjaman_jenis_id' => $kind->id, 'kode' => 'PIN-'.Str::ulid(),
                'jumlah' => (int) $data['loan_total'], 'tenor_bulan' => (int) $data['loan_term'],
                'bunga_persen_per_bulan' => $kind->bunga_persen_per_bulan,
                'metode_bayar' => $data['payment_method'], 'status' => 'diajukan',
                'tanggal_pengajuan' => today()->toDateString(),
            ]);
        }, 3);
    }
}
