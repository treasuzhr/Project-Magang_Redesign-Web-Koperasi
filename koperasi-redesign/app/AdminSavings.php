<?php

namespace App;

use App\Models\Anggota;
use App\Models\SimpananTransaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminSavings
{
    public function record(array $input): SimpananTransaksi
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $data = $this->validate($input);
        // Aturan sementara: penarikan biasa hanya sukarela; pokok/wajib saat keluar. Konfirmasi ke pengurus.
        if ($data['tipe'] === 'tarik' && $data['jenis'] !== 'sukarela') {
            throw ValidationException::withMessages(['jenis' => 'Penarikan di sini hanya untuk simpanan sukarela.']);
        }

        return $this->persist($data);
    }

    public function validate(array $input): array
    {
        return Validator::make($input, [
            'anggota_id' => ['required', 'integer', Rule::exists('anggotas', 'id')],
            'jenis' => ['required', Rule::in(SimpananTransaksi::JENIS)], 'tipe' => ['required', Rule::in(SimpananTransaksi::TIPE)],
            'nominal' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'keterangan' => ['nullable', 'string', 'max:2000'],
        ], ['nominal.min' => 'Jumlah harus lebih dari nol.', 'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan.'])->validate();
    }

    public function correct(SimpananTransaksi $original, string $reason): SimpananTransaksi
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $data = Validator::make(['keterangan' => trim($reason)], ['keterangan' => ['required', 'string', 'max:1800']], ['keterangan.required' => 'Alasan koreksi wajib diisi.'])->validate();

        // Koreksi pencatatan bukan penarikan anggota; tetap harus lolos pemeriksaan saldo.
        return $this->persist(['anggota_id' => $original->anggota_id, 'jenis' => $original->jenis, 'tipe' => $original->tipe === 'setor' ? 'tarik' : 'setor', 'nominal' => $original->nominal, 'tanggal' => today()->toDateString(), 'keterangan' => 'Koreksi transaksi #'.$original->id.': '.$data['keterangan']]);
    }

    private function persist(array $data): SimpananTransaksi
    {
        return DB::transaction(function () use ($data): SimpananTransaksi {
            $member = Anggota::query()->lockForUpdate()->findOrFail($data['anggota_id']);
            $balance = (int) $member->simpananTransaksis()->where('jenis', $data['jenis'])->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END),0) AS saldo")->value('saldo');
            if ($data['tipe'] === 'tarik' && $data['nominal'] > $balance) {
                throw ValidationException::withMessages(['nominal' => 'Jumlah penarikan melebihi saldo jenis simpanan ini.']);
            }

            return SimpananTransaksi::create($data + ['sumber' => 'manual']);
        });
    }
}
