<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'bunga_persen_per_bulan', 'plafon_maks', 'tenor_maks', 'butuh_dokumen_akad'])]
class PinjamanJenis extends Model
{
    protected $table = 'pinjaman_jenis';

    public const KODE = ['toko', 'usipa', 'usipa_khusus'];

    protected function casts(): array
    {
        return [
            'bunga_persen_per_bulan' => 'decimal:4',
            'plafon_maks' => 'integer',
            'tenor_maks' => 'integer',
            'butuh_dokumen_akad' => 'boolean',
        ];
    }

    public function pinjamans(): HasMany
    {
        return $this->hasMany(Pinjaman::class, 'pinjaman_jenis_id');
    }
}
