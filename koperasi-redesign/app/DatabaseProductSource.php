<?php

namespace App;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DatabaseProductSource implements ProductSource
{
    public function categories(): array
    {
        $decoration = collect(config('product_categories', []))->keyBy('slug');

        return Kategori::withCount(['produks' => fn ($q) => $q->where('aktif', true)])->orderBy('id')->get()->map(fn ($category): array => [
            'slug' => $category->slug, 'name' => $category->nama, 'products_count' => $category->produks_count,
            'description' => $decoration->get($category->slug)['description'] ?? '',
            'image' => $decoration->get($category->slug)['image'] ?? null,
        ])->all();
    }

    public function product(Produk $product): array
    {
        $photo = $product->foto;
        if ($photo && ! str_starts_with($photo, 'assets/') && ! preg_match('#^https?://#', $photo)) {
            $photo = Storage::disk('public')->url($photo);
        }

        return ['id' => $product->id, 'name' => $product->nama, 'display_name' => ProductDisplayName::format($product->nama),
            'price' => $product->harga, 'category' => $product->kategori?->slug, 'category_label' => $product->kategori?->nama, 'image' => $photo];
    }

    public function paginate(array $filters, bool $invalid, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Produk::with('kategori')->where('aktif', true);
        if ($invalid) {
            $query->whereRaw('1 = 0');
        }
        if ($filters['q'] !== '') {
            $query->where('nama', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%');
        }
        if ($filters['kategori'] !== '') {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $filters['kategori']));
        }
        if ($filters['min'] !== '') {
            $query->where('harga', '>=', (int) $filters['min']);
        }
        if ($filters['max'] !== '') {
            $query->where('harga', '<=', (int) $filters['max']);
        }
        $sort = $filters['urut'];

        return $query->orderBy(str_starts_with($sort, 'price') ? 'harga' : 'nama', str_ends_with($sort, 'desc') ? 'desc' : 'asc')
            ->orderBy('id')->paginate($perPage, ['*'], 'page', $page)->through(fn (Produk $product): array => $this->product($product));
    }

    public function all(): Collection
    {
        return Produk::with('kategori')->where('aktif', true)->orderBy('nama')->get()->map(fn (Produk $product): array => $this->product($product));
    }

    public function isExample(): bool
    {
        return false;
    }
}
