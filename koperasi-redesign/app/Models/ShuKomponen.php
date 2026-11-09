<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shu_periode_id', 'kode', 'unit', 'metode', 'pool_nominal', 'persentase'])]
class ShuKomponen extends Model
{
    protected $table = 'shu_komponens';

    public const UNIT = ['toko', 'usipa', 'usipa_khusus', 'jasa_umum'];

    public const METODE = ['proporsional', 'persentase_tetap', 'bagi_rata_prorata'];

    public const KODE = ['simpanan_pokok_wajib', 'belanja', 'simpanan_sukarela', 'jasa_usipa_anggota', 'jasa_usipa_khusus', 'jasa_umum'];

    protected function casts(): array
    {
        return [
            'pool_nominal' => 'integer',
            'persentase' => 'decimal:4',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ShuPeriode::class, 'shu_periode_id');
    }

    public function shuAnggotas(): HasMany
    {
        return $this->hasMany(ShuAnggota::class, 'shu_komponen_id');
    }
}
