<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tahun', 'status', 'shu_bersih', 'ditetapkan_at'])]
class ShuPeriode extends Model
{
    protected $table = 'shu_periodes';

    public const STATUSES = ['draft', 'ditetapkan'];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'shu_bersih' => 'integer',
            'ditetapkan_at' => 'datetime',
        ];
    }

    public function komponens(): HasMany
    {
        return $this->hasMany(ShuKomponen::class, 'shu_periode_id');
    }

    public function shuAnggotas(): HasMany
    {
        return $this->hasMany(ShuAnggota::class, 'shu_periode_id');
    }
}
