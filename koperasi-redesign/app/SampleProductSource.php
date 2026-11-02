<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SampleProductSource implements ProductSource
{
    public function all(): Collection
    {
        // TODO: ganti binding ProductSource dengan sumber data resmi saat tersedia.
        // Pemetaan kategori tampilan pada data contoh ini belum dikonfirmasi.
        return collect(config('product_catalog.samples', []))->map(static fn (array $product): array => [
            'id' => Str::slug($product['name']),
            'name' => $product['name'],
            'price' => $product['price'],
            'category' => $product['category'],
            'image' => $product['image'] ?? null,
        ]);
    }

    public function isExample(): bool
    {
        return true;
    }
}
