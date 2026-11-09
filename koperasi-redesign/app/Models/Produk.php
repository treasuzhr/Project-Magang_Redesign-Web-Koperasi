<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kategori_id', 'nama', 'harga', 'aktif', 'foto'])]
class Produk extends Model
{
    protected $table = 'produks';

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function pesananItems(): HasMany
    {
        return $this->hasMany(PesananItem::class, 'produk_id');
    }
}
