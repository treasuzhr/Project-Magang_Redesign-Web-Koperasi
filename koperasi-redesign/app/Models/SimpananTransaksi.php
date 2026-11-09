<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['anggota_id', 'jenis', 'tipe', 'nominal', 'tanggal', 'sumber', 'keterangan'])]
class SimpananTransaksi extends Model
{
    protected $table = 'simpanan_transaksis';

    public const JENIS = ['pokok', 'wajib', 'sukarela', 'khusus'];

    public const TIPE = ['setor', 'tarik'];

    public const SUMBER = ['potong_gaji', 'manual', 'shu'];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }
}
