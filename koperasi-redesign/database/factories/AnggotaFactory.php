<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Anggota> */
class AnggotaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nomor_anggota' => 'DEMO-'.fake()->unique()->numerify('######'),
            'nama' => 'Anggota Fiktif '.fake()->numerify('######'),
            'tanggal_masuk' => '2021-01-01',
            'status' => 'aktif',
        ];
    }
}
