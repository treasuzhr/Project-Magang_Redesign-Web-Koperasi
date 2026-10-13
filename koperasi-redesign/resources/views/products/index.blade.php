@extends('layouts.public')

@section('content')
    <main class="catalog-page page-width">
        <x-section-heading eyebrow="Jelajahi" title="Katalog Produk" id="catalog-title" />

        <div class="catalog-data-notice" role="status">
            <strong>Data produk belum terhubung.</strong>
            <span>Pencarian, harga, stok, pengurutan, dan halaman hasil memerlukan sumber data produk resmi. Kategori di sini hanya untuk tampilan; produk dan kategori bisnis tidak diubah. Arti “Titip Barang” juga perlu dikonfirmasi sebelum dipetakan.</span>
        </div>

        <form class="catalog-filters" method="GET" action="{{ route('products.index') }}">
            <input type="hidden" name="view" value="{{ $filters['view'] ?? 'grid' }}">
            <div class="catalog-search">
                <label for="product-search">Cari nama produk</label>
                <div class="catalog-search__control">
                    <input
                        id="product-search"
                        name="q"
                        type="search"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Masukkan nama produk"
                        maxlength="100"
                    >
                    <button class="button button--primary" type="submit">Cari produk</button>
                </div>
            </div>

            <div class="catalog-filter-grid">
                <div class="catalog-field">
                    <label for="category-filter">Kategori tampilan</label>
                    <select id="category-filter" name="category">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category['slug'] }}" @selected(($filters['category'] ?? '') === $category['slug'])>
                                {{ $category['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="catalog-field">
                    <label for="min-price">Harga minimum</label>
                    <input id="min-price" name="min_price" type="number" min="0" step="1" inputmode="numeric"
                        value="{{ $filters['min_price'] ?? '' }}" placeholder="Rp minimum">
                </div>
                <div class="catalog-field">
                    <label for="max-price">Harga maksimum</label>
                    <input id="max-price" name="max_price" type="number" min="0" step="1" inputmode="numeric"
                        value="{{ $filters['max_price'] ?? '' }}" placeholder="Rp maksimum">
                </div>
                <div class="catalog-field">
                    <label for="sort-products">Urutkan</label>
                    <select id="sort-products" name="sort">
                        <option value="name_asc" @selected(($filters['sort'] ?? 'name_asc') === 'name_asc')>Nama: A–Z</option>
                        <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>Nama: Z–A</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Harga: terendah</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Harga: tertinggi</option>
                    </select>
                </div>
            </div>

            <div class="catalog-filter-actions">
                <p>Filter dan urutan akan diterapkan setelah data produk tersedia.</p>
                <a class="catalog-reset" href="{{ route('products.index') }}">Hapus filter</a>
            </div>
        </form>

        <div class="catalog-results-heading">
            <div>
                <h2>Hasil pencarian</h2>
                <p>Belum ada data produk untuk ditampilkan.</p>
            </div>
            <div class="catalog-view-controls" aria-label="Pilihan tampilan">
                <button type="button" aria-pressed="true" disabled>Grid</button>
                <button type="button" aria-pressed="false" disabled>List</button>
            </div>
        </div>

        <section class="catalog-empty-state" aria-labelledby="catalog-empty-title">
            <div class="catalog-empty-state__icon" aria-hidden="true">⌕</div>
            <h2 id="catalog-empty-title">Katalog sedang disiapkan</h2>
            <p>Antarmuka pencarian sudah tersedia, tetapi project ini belum memiliki sumber data produk yang bisa dicari atau difilter.</p>
        </section>

        <nav class="catalog-pagination" aria-label="Halaman katalog">
            <button type="button" disabled aria-disabled="true">Sebelumnya</button>
            <span>Halaman akan tersedia setelah data katalog terhubung</span>
            <button type="button" disabled aria-disabled="true">Berikutnya</button>
        </nav>
    </main>
@endsection
