<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shu_periode_id', 'shu_komponen_id', 'anggota_id', 'kode_komponen', 'jumlah_dasar', 'bulan_aktif', 'shu'])]
class ShuAnggota extends Model
{
    protected $table = 'shu_anggotas';

    protected function casts(): array
    {
        return [
            'jumlah_dasar' => 'integer',
            'bulan_aktif' => 'integer',
            'shu' => 'integer',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ShuPeriode::class, 'shu_periode_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(ShuKomponen::class, 'shu_komponen_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }
}
