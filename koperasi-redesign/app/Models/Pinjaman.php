<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['anggota_id', 'pinjaman_jenis_id', 'kode', 'jumlah', 'tenor_bulan', 'bunga_persen_per_bulan', 'metode_bayar', 'status', 'tanggal_pengajuan', 'tanggal_disetujui', 'catatan_admin', 'dokumen_akad_path'])]
class Pinjaman extends Model
{
    protected $table = 'pinjamans';

    public const STATUSES = ['diajukan', 'disetujui', 'ditolak', 'berjalan', 'lunas'];

    public const METODE_BAYAR = ['potong_gaji', 'tunjangan_kinerja'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tenor_bulan' => 'integer',
            'bunga_persen_per_bulan' => 'decimal:4',
            'tanggal_pengajuan' => 'date',
            'tanggal_disetujui' => 'date',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(PinjamanJenis::class, 'pinjaman_jenis_id');
    }

    public function angsurans(): HasMany
    {
        return $this->hasMany(Angsuran::class, 'pinjaman_id');
    }
}
