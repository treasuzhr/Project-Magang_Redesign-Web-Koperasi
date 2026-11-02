@php
    $catalogUrl = static fn (array $changes = []) => route('products.index', array_filter(array_replace($query, $changes), static fn ($value) => $value !== null && $value !== ''));
@endphp
<form id="catalog-filters" class="pc-layout" action="{{ route('products.index') }}" method="GET" data-catalog-form>
    <input type="hidden" name="kategori" value="{{ $filters['kategori'] }}" data-selected-category>
    <input type="hidden" name="tampilan" value="{{ $filters['tampilan'] }}" data-selected-view>
    <aside class="pc-sidebar" aria-label="Cari dan filter produk">
        <div class="pc-search">
            <label class="sr-only" for="catalog-search">Cari nama produk</label>
            <x-catalog-icon name="search" />
            <input id="catalog-search" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Cari nama produk" maxlength="100" autocomplete="off" form="catalog-filters">
            <button type="submit" aria-label="Cari nama produk" form="catalog-filters"><span aria-hidden="true">&#8629;</span></button>
        </div>
        <div class="pc-filter-body" data-filter-body>
            <fieldset class="pc-category-filter">
                <legend>Kategori</legend>
                <div class="pc-categories">
                    <button type="submit" name="kategori" value="" form="catalog-filters" class="{{ $filters['kategori'] === '' ? 'is-active' : '' }}" aria-pressed="{{ $filters['kategori'] === '' ? 'true' : 'false' }}" data-category-filter><span>Semua kategori</span><small>{{ $sourceTotal }}</small></button>
                    @foreach ($categories as $category)
                        <button type="submit" name="kategori" value="{{ $category['slug'] }}" form="catalog-filters" @disabled(($categoryCounts[$category['slug']] ?? 0) === 0) @if (($categoryCounts[$category['slug']] ?? 0) === 0) aria-disabled="true" @endif class="{{ $filters['kategori'] === $category['slug'] ? 'is-active' : '' }}" aria-pressed="{{ $filters['kategori'] === $category['slug'] ? 'true' : 'false' }}" data-category-filter><span>{{ $category['name'] }}</span><small>{{ $categoryCounts[$category['slug']] ?? 0 }}</small></button>
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="pc-price-filter">
                <legend>Harga</legend>
                <div class="pc-price-inputs">
                    <label for="catalog-min"><span class="sr-only">Harga minimum</span><span aria-hidden="true">Rp</span><input id="catalog-min" type="text" name="min" inputmode="numeric" pattern="[0-9. ]*" value="{{ $filters['min'] }}" placeholder="Minimum" form="catalog-filters" data-price-input @if ($errors->has('min')) aria-invalid="true" aria-describedby="catalog-filter-errors" @endif></label>
                    <span class="pc-price-separator" aria-hidden="true">&mdash;</span>
                    <label for="catalog-max"><span class="sr-only">Harga maksimum</span><span aria-hidden="true">Rp</span><input id="catalog-max" type="text" name="max" inputmode="numeric" pattern="[0-9. ]*" value="{{ $filters['max'] }}" placeholder="Maksimum" form="catalog-filters" data-price-input @if ($errors->has('max')) aria-invalid="true" aria-describedby="catalog-filter-errors" @endif></label>
                </div>
                <div class="pc-price-presets">
                    <a href="{{ $catalogUrl(['min' => null, 'max' => 9999, 'page' => null]) }}" data-price-preset data-min="" data-max="9999">Di bawah Rp 10.000</a>
                    <a href="{{ $catalogUrl(['min' => 10000, 'max' => 50000, 'page' => null]) }}" data-price-preset data-min="10000" data-max="50000">Rp 10.000 - Rp 50.000</a>
                    <a href="{{ $catalogUrl(['min' => 50001, 'max' => null, 'page' => null]) }}" data-price-preset data-min="50001" data-max="">Di atas Rp 50.000</a>
                </div>
                <button type="submit" form="catalog-filters" class="pc-apply-price">Terapkan harga</button>
            </fieldset>
            @if ($hasFilters)<a class="pc-reset" href="{{ route('products.index', ['tampilan' => $filters['tampilan'], 'urut' => $filters['urut']]) }}" data-catalog-link>Hapus filter <span aria-hidden="true">&times;</span></a>@endif
        </div>
    </aside>
    <section class="pc-results" aria-label="Hasil katalog" data-catalog-results>
        <div class="pc-toolbar">
            <p class="pc-results-count" role="status" aria-live="polite"><strong>{{ $products->total() }}</strong> produk</p>
            <button type="button" class="pc-filter-toggle" data-open-filters><x-catalog-icon name="filter" /> Filter</button>
            <div class="pc-toolbar-controls">
                <label class="pc-sort" for="catalog-sort"><span>Urutkan</span><select id="catalog-sort" name="urut" form="catalog-filters">
                    @foreach (['name_asc' => 'Nama A-Z', 'name_desc' => 'Nama Z-A', 'price_asc' => 'Termurah', 'price_desc' => 'Termahal'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['urut'] === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
                <div class="pc-view-switch" role="group" aria-label="Tampilan produk" data-view-switch>
                    <span class="pc-view-indicator" aria-hidden="true"></span>
                    <button type="submit" name="tampilan" value="grid" form="catalog-filters" aria-pressed="{{ $filters['tampilan'] === 'grid' ? 'true' : 'false' }}" aria-label="Tampilan Grid" title="Grid"><x-catalog-icon name="grid" /></button>
                    <button type="submit" name="tampilan" value="list" form="catalog-filters" aria-pressed="{{ $filters['tampilan'] === 'list' ? 'true' : 'false' }}" aria-label="Tampilan List" title="List"><x-catalog-icon name="list" /></button>
                </div>
                <button class="pc-sort-apply" type="submit" form="catalog-filters">Terapkan urutan</button>
            </div>
        </div>
        @if ($errors->any())
            <div class="pc-errors" id="catalog-filter-errors" role="alert">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
        @endif
        @if ($activeFilters)
            <div class="pc-active-filters" aria-label="Filter aktif">
                @foreach ($activeFilters as $filter)<a href="{{ $catalogUrl($filter['remove']) }}" data-catalog-link aria-label="Hapus filter {{ $filter['label'] }}">{{ $filter['label'] }} <span aria-hidden="true">&times;</span></a>@endforeach
            </div>
        @endif
        @if (!$sourceAvailable)
            <div class="pc-empty"><x-catalog-icon name="bag" /><h2>Katalog sedang disiapkan</h2><p>Silakan kunjungi kembali untuk melihat produk koperasi.</p></div>
        @elseif ($products->isEmpty())
            <div class="pc-empty"><x-catalog-icon name="search" /><h2>Produk tidak ditemukan</h2><p>Coba kata kunci lain atau longgarkan filter pencarian.</p><a class="button button--primary" href="{{ route('products.index') }}" data-catalog-link>Atur ulang filter</a></div>
        @else
            <div class="pc-product-grid {{ $filters['tampilan'] === 'list' ? 'is-list' : '' }}" data-product-grid>
                @foreach ($products as $product)<x-product-card :product="$product" :index="$loop->index" />@endforeach
            </div>
        @endif
        <div class="pc-skeleton" data-catalog-skeleton aria-hidden="true" hidden>@for ($i = 0; $i < 6; $i++)<div><span></span><i></i><i></i></div>@endfor</div>
        @if ($products->hasPages())
            <nav class="pc-pagination" aria-label="Halaman katalog">
                @if ($products->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $products->previousPageUrl() }}" data-catalog-link>Sebelumnya</a>@endif
                @php $lastShown = 0; @endphp
                @for ($page = 1; $page <= $products->lastPage(); $page++)
                    @if ($page === 1 || $page === $products->lastPage() || abs($page - $products->currentPage()) <= 1)
                        @if ($lastShown && $page - $lastShown > 1)<span class="pc-page-gap" aria-hidden="true">&hellip;</span>@endif
                        <a href="{{ $products->url($page) }}" @if ($page === $products->currentPage()) aria-current="page" @endif data-catalog-link>{{ $page }}</a>
                        @php $lastShown = $page; @endphp
                    @endif
                @endfor
                @if ($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}" data-catalog-link>Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
            </nav>
        @endif
    </section>
</form>
