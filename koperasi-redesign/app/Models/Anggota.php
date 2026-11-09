<?php

namespace App\Models;

use Database\Factories\AnggotaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'nomor_anggota', 'nama', 'pangkat_golongan', 'jabatan', 'avatar_path', 'tanggal_masuk', 'tanggal_keluar', 'status'])]
class Anggota extends Model
{
    /** @use HasFactory<AnggotaFactory> */
    use HasFactory;

    protected $table = 'anggotas';

    public const STATUSES = ['aktif', 'keluar', 'pensiun', 'pindah'];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function simpananTransaksis(): HasMany
    {
        return $this->hasMany(SimpananTransaksi::class, 'anggota_id');
    }

    public function pinjamans(): HasMany
    {
        return $this->hasMany(Pinjaman::class, 'anggota_id');
    }

    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'anggota_id');
    }

    public function shuAnggotas(): HasMany
    {
        return $this->hasMany(ShuAnggota::class, 'anggota_id');
    }

    public function saldoSimpanan(string $jenis, ?string $sampaiTanggal = null): int
    {
        if (! in_array($jenis, SimpananTransaksi::JENIS, true)) {
            throw new \InvalidArgumentException('Jenis simpanan tidak dikenal.');
        }

        return (int) $this->simpananTransaksis()->where('jenis', $jenis)
            ->when($sampaiTanggal !== null, fn ($query) => $query->whereDate('tanggal', '<=', $sampaiTanggal))
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'setor' THEN nominal ELSE -CAST(nominal AS SIGNED) END), 0) AS saldo")
            ->value('saldo');
    }

    protected function saldoPokok(): Attribute
    {
        return Attribute::get(fn (): int => $this->saldoSimpanan('pokok'));
    }

    protected function saldoWajib(): Attribute
    {
        return Attribute::get(fn (): int => $this->saldoSimpanan('wajib'));
    }

    protected function saldoSukarela(): Attribute
    {
        return Attribute::get(fn (): int => $this->saldoSimpanan('sukarela'));
    }

    protected function saldoKhusus(): Attribute
    {
        return Attribute::get(fn (): int => $this->saldoSimpanan('khusus'));
    }
}
