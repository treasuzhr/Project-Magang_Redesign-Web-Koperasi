<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pesanan_id', 'produk_id', 'nama', 'harga', 'qty', 'subtotal'])]
class PesananItem extends Model
{
    protected $table = 'pesanan_items';

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'qty' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
