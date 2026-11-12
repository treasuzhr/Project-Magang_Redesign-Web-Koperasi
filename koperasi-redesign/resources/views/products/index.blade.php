@extends('layouts.public')

@section('title', 'Katalog Produk | Primkop Pullahta Hankam')
@section('description', 'Cari dan lihat kebutuhan harian koperasi.')

@push('page-assets')
    @vite(['resources/css/products.css', 'resources/js/products.js'])
@endpush

@section('content')
    <main class="product-catalog" data-product-catalog data-cart-products-url="{{ route('cart.products') }}">
        <header class="pc-header">
            <div>
                <p class="section-eyebrow">Jelajahi</p>
                <h1>Katalog <span class="pc-title-accent">Produk</span></h1>
                <p>Cari dan lihat kebutuhan harian koperasi.</p>
            </div>
        </header>
        <div data-catalog-shell>@include('products.catalog')</div>
        <dialog class="pc-sheet" aria-labelledby="pc-sheet-title" data-filter-sheet>
            <header><h2 id="pc-sheet-title">Filter produk</h2><button type="button" class="pc-icon-button" data-close-filters aria-label="Tutup filter">&times;</button></header>
            <div data-sheet-content></div>
            <footer><button class="button button--primary" type="submit" form="catalog-filters" data-apply-filters>Terapkan</button></footer>
        </dialog>
        <div class="pc-toast" role="status" aria-live="polite" data-cart-toast hidden>Masuk keranjang</div>
        <a class="pc-mobile-cart" href="{{ route('cart.index') }}" aria-label="Buka keranjang" data-mobile-cart hidden>
            <x-catalog-icon name="bag" />
            <span><strong data-mobile-cart-count>0 barang</strong><span aria-hidden="true"> &middot; </span><span data-mobile-cart-total>Rp 0</span></span>
        </a>
    </main>
@endsection
