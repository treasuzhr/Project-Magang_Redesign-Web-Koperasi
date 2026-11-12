<?php

namespace App\Http\Requests;

use App\LoanSimulator;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'anggota' && $this->user()->anggota()->where('status', 'aktif')->exists();
    }

    public function validationData(): array
    {
        $data = parent::validationData();
        $data['loan_total'] = LoanSimulator::normalizeAmount($data['loan_total'] ?? null) ?? ($data['loan_total'] ?? null);

        return $data;
    }

    public function rules(): array
    {
        $kind = is_string($this->input('loan_type')) ? PinjamanJenis::where('kode', $this->input('loan_type'))->first() : null;

        return [
            'loan_type' => ['bail', 'required', 'string', Rule::in(PinjamanJenis::KODE), Rule::exists('pinjaman_jenis', 'kode')],
            'loan_total' => ['bail', 'required', 'integer', 'min:1', 'max:'.($kind?->plafon_maks ?? PHP_INT_MAX)],
            'loan_term' => ['bail', 'required', 'integer', 'min:1', 'max:'.($kind?->tenor_maks ?? 65535)],
            'payment_method' => [$this->routeIs('anggota.pinjaman.simulasi') ? 'sometimes' : 'required', Rule::in(Pinjaman::METODE_BAYAR)],
        ];
    }

    public function messages(): array
    {
        return [
            'loan_type.required' => 'Pilih jenis pinjaman dulu.', 'loan_type.string' => 'Pilih jenis pinjaman dulu.',
            'loan_type.in' => 'Pilih jenis pinjaman dulu.', 'loan_type.exists' => 'Pilih jenis pinjaman dulu.',
            'loan_total.required' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
            'loan_total.integer' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
            'loan_total.min' => 'Total pinjaman wajib diisi dengan angka, contoh 10000000.',
            'loan_total.max' => 'Total pinjaman melebihi plafon jenis pinjaman yang dipilih.',
            'loan_term.required' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'loan_term.integer' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'loan_term.min' => 'Tenor wajib diisi dengan angka bulat, contoh 12.',
            'loan_term.max' => 'Tenor melebihi batas jenis pinjaman yang dipilih.',
            'payment_method.required' => 'Pilih metode pembayaran dulu.',
            'payment_method.in' => 'Pilih metode pembayaran dulu.',
        ];
    }
}
