<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pinjaman_id', 'angsuran_ke', 'jatuh_tempo', 'pokok', 'bunga', 'total', 'status', 'tanggal_bayar'])]
class Angsuran extends Model
{
    protected $table = 'angsurans';

    public const STATUSES = ['belum', 'lunas', 'tunggak'];

    protected function casts(): array
    {
        return [
            'angsuran_ke' => 'integer',
            'pokok' => 'integer',
            'bunga' => 'integer',
            'total' => 'integer',
            'jatuh_tempo' => 'date',
            'tanggal_bayar' => 'date',
        ];
    }

    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class, 'pinjaman_id');
    }
}
