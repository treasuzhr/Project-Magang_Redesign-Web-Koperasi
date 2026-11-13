<?php

namespace App;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMemberAccounts
{
    private function validate(array $data, ?Anggota $member = null): array
    {
        return Validator::make($data, [
            'nip' => ['required', 'string', 'max:255', Rule::unique('users', 'nip')->ignore($member?->user_id)],
            'nama' => ['required', 'string', 'max:255'],
            'nomor_anggota' => ['nullable', 'string', 'max:255', Rule::unique('anggotas', 'nomor_anggota')->ignore($member?->id)],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'tanggal_masuk' => ['required', 'date'], 'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'status' => ['required', Rule::in(Anggota::STATUSES)],
            ...($member === null ? ['password' => ['required', 'string', 'min:8', 'max:1024']] : []),
        ], [
            'nip.required' => 'NIP wajib diisi.', 'nip.unique' => 'NIP sudah digunakan.',
            'nama.required' => 'Nama wajib diisi.', 'nomor_anggota.unique' => 'Nomor anggota sudah digunakan.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.', 'tanggal_masuk.date' => 'Tanggal masuk tidak valid.',
            'tanggal_keluar.date' => 'Tanggal keluar tidak valid.', 'tanggal_keluar.after_or_equal' => 'Tanggal keluar tidak boleh sebelum tanggal masuk.',
            'status.in' => 'Status anggota tidak valid.', 'password.required' => 'Password awal wajib diisi.',
            'password.min' => 'Password awal minimal 8 karakter.',
        ])->validate();
    }

    public function create(array $input): Anggota
    {
        $data = $this->validate($input);

        return DB::transaction(function () use ($data): Anggota {
            $user = User::create(['nip' => $data['nip'], 'name' => $data['nama'], 'password' => $data['password'], 'role' => 'anggota', 'must_change_password' => true]);

            return $user->anggota()->create(Arr::except($data, ['nip', 'password']));
        });
    }

    public function update(Anggota $member, array $input): Anggota
    {
        abort_unless($member->user->role === 'anggota', 403);
        $data = $this->validate($input, $member);

        return DB::transaction(function () use ($member, $data): Anggota {
            $member->user->update(['nip' => $data['nip'], 'name' => $data['nama']]);
            $member->update(Arr::except($data, ['nip', 'password']));

            return $member->refresh();
        });
    }

    public function resetPassword(Anggota $member): string
    {
        abort_unless($member->user->role === 'anggota', 403);
        $password = Str::password(16);
        $member->user->forceFill(['password' => $password, 'must_change_password' => true, 'remember_token' => Str::random(60)])->save();

        return $password;
    }
}
