<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['anggota_id', 'kode', 'status', 'total', 'metode_bayar', 'catatan', 'tanggal'])]
class Pesanan extends Model
{
    protected $table = 'pesanans';

    public const STATUSES = ['baru', 'diproses', 'siap', 'selesai', 'batal'];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'tanggal' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PesananItem::class, 'pesanan_id');
    }
}
