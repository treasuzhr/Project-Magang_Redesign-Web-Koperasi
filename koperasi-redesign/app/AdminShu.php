<?php

namespace App;

use App\Models\ShuPeriode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminShu
{
    // Persentase dan dasar simpanan adalah parameter sementara: konfirmasi ke pengurus.
    public const POOLS = ['toko' => 'Toko', 'usipa' => 'Usipa Anggota', 'usipa_khusus' => 'Usipa Khusus', 'jasa_umum' => 'Jasa Umum'];

    public const PERCENTAGES = ['toko_simpanan' => 'Toko: Pokok + Wajib (%)', 'toko_belanja' => 'Toko: Belanja (%)', 'usipa_sukarela' => 'Usipa: Sukarela (%)', 'usipa_jasa' => 'Usipa: Jasa (%)', 'jasa_tetap' => 'Persen tetap Jasa Sipa Angg'];

    public function save(array $data, ?ShuPeriode $period = null): ShuPeriode
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $rules = ['tahun' => ['required', 'integer', 'between:1900,9998', Rule::unique('shu_periodes', 'tahun')->ignore($period?->id)], 'dasar_saldo' => ['required', Rule::in(['akhir_tahun', 'rata_bulanan'])]];
        foreach (self::POOLS as $key => $label) {
            $rules['pool_'.$key] = ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX];
        }
        foreach (self::PERCENTAGES as $key => $label) {
            $rules[$key] = ['required', 'numeric', 'between:0,100', 'decimal:0,4'];
        }
        $attributes = ['tahun' => 'Tahun buku', 'dasar_saldo' => 'Dasar saldo'] + self::PERCENTAGES;
        foreach (self::POOLS as $key => $label) {
            $attributes['pool_'.$key] = 'Pool '.$label;
        }
        $validator = Validator::make($data, $rules, ['required' => ':attribute wajib diisi.', 'integer' => ':attribute harus bilangan bulat.', 'numeric' => ':attribute harus berupa angka.', 'between' => ':attribute di luar batas.', 'min' => ':attribute tidak boleh negatif.', 'max' => ':attribute melebihi batas.', 'unique' => 'Tahun sudah tersedia.', 'in' => 'Pilihan tidak valid.', 'decimal' => 'Maksimal empat angka desimal.'], $attributes);
        $validator->after(function ($validator) use ($data): void {
            foreach ([['toko_simpanan', 'toko_belanja'], ['usipa_sukarela', 'usipa_jasa']] as [$a,$b]) {
                if (is_numeric($data[$a] ?? null) && is_numeric($data[$b] ?? null) && (int) round(((float) $data[$a] + (float) $data[$b]) * 10000) !== 1000000) {
                    $validator->errors()->add($b, 'Pasangan persentase harus berjumlah 100%.');
                }
            }
        });
        $valid = $validator->validate();

        return DB::transaction(function () use ($valid, $period): ShuPeriode {
            $record = $period ? ShuPeriode::query()->lockForUpdate()->findOrFail($period->id) : new ShuPeriode;
            if ($record->exists && $record->status !== 'draft') {
                throw ValidationException::withMessages(['tahun' => 'Periode ditetapkan sudah terkunci.']);
            }
            $year = (int) $valid['tahun'];
            unset($valid['tahun']);
            $previous = array_intersect_key($record->parameter ?? [], $valid);
            $unchanged = $record->exists && $record->tahun === $year && count($previous) === count($valid);
            foreach ($valid as $key => $value) {
                $unchanged = $unchanged && (string) ($previous[$key] ?? '') === (string) $value;
            }
            if ($unchanged) {
                return $record;
            }
            if ($record->exists) {
                $record->shuAnggotas()->delete();
                $record->komponens()->delete();
            }
            $record->fill(['tahun' => $year, 'parameter' => $valid, 'status' => 'draft', 'shu_bersih' => null])->save();

            return $record;
        });
    }

    public function establish(ShuPeriode $period): ShuPeriode
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return DB::transaction(function () use ($period): ShuPeriode {
            $record = ShuPeriode::query()->lockForUpdate()->findOrFail($period->id);
            if ($record->status !== 'draft' || empty($record->parameter['report']['calculated_at'])) {
                throw ValidationException::withMessages(['periode' => 'Hanya draf yang sudah dihitung yang dapat ditetapkan.']);
            }
            $total = (int) $record->shuAnggotas()->sum('shu');
            if ($record->shu_bersih !== $total) {
                throw ValidationException::withMessages(['periode' => 'Total hasil berubah. Hitung ulang sebelum menetapkan.']);
            }
            // Aturan sementara: tidak ada buka kembali; konfirmasi ke pengurus.
            $record->update(['status' => 'ditetapkan', 'ditetapkan_at' => now()]);

            return $record->refresh();
        });
    }
}
