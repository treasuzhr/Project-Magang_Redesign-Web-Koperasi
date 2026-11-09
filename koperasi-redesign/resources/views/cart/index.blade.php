@extends('layouts.public')
@section('title', 'Keranjang | Primkop Pullahta Hankam')
@section('description', 'Lihat dan atur produk di keranjang Anda.')
@section('content')
    @vite('resources/js/cart.js')
    <main class="shopping-cart" data-shopping-cart data-source-available="{{ $sourceAvailable ? 'true' : 'false' }}">
        <header class="sc-header">
            <div><p class="section-eyebrow">Pilihan Anda</p><h1>Keranjang <span class="sc-title-accent">belanja</span></h1><p>Periksa kembali produk dan jumlahnya.</p></div>
            <a class="sc-back" href="{{ route('products.index') }}">Lanjut belanja <span aria-hidden="true">&rarr;</span></a>
        </header>
        <p data-cart-loading role="status">Memuat keranjang&hellip;</p>
        <noscript><p class="sc-notice">Aktifkan JavaScript untuk melihat dan mengatur keranjang.</p></noscript>
        <p class="sc-notice" data-storage-notice hidden>Keranjang hanya tersimpan selama halaman ini terbuka.</p>
        @if (!$sourceAvailable)
            <div class="sc-empty"><x-catalog-icon name="bag" /><h2>Keranjang belum dapat dimuat</h2><p>Data produk belum tersedia. Coba muat ulang halaman.</p><a class="sc-primary" href="{{ route('cart.index') }}">Muat ulang</a></div>
        @else
            <section class="sc-empty" data-cart-empty hidden>
                <div class="sc-empty__icon"><x-catalog-icon name="cart" /></div>
                <h2>Keranjang masih kosong</h2><p>Produk yang Anda tambahkan akan muncul di sini.</p>
                <a class="sc-primary" href="{{ route('products.index') }}">Lihat produk</a>
            </section>
            <div class="sc-layout" data-cart-filled hidden>
                <section class="sc-items" aria-label="Produk di keranjang">
                    <div class="sc-items__heading"><h2>Produk Anda</h2><span data-cart-count></span></div>
                    <div class="sc-columns" aria-hidden="true"><span>Produk</span><span>Jumlah</span><span>Subtotal</span><span></span></div>
                    @foreach ($products as $product)
                        <article class="sc-item" data-cart-item data-id="{{ $product['id'] }}" data-name="{{ $product['display_name'] }}" data-price="{{ $product['price'] }}" hidden>
                            <div class="sc-product">
                                <div class="sc-tile">
                                    @if ($product['image'])
                                        <img src="{{ asset($product['image']) }}" alt="" width="96" height="80" loading="lazy">
                                    @else
                                        <x-catalog-icon :name="$product['category'] ?? 'bag'" />
                                    @endif
                                </div>
                                <div class="sc-identity"><h3>{{ $product['display_name'] }}</h3>
                                    @if ($product['category_label'])<p>{{ $product['category_label'] }}</p>@endif
                                    <span>Rp {{ number_format($product['price'], 0, ',', '.') }} <span class="sc-unit">/ barang</span></span>
                                </div>
                            </div>
                            <div class="sc-stepper" role="group" aria-label="Jumlah {{ $product['display_name'] }}">
                                <button type="button" data-cart-change="-1" aria-label="Kurangi {{ $product['display_name'] }}">&minus;</button>
                                <output data-cart-quantity aria-label="Jumlah {{ $product['display_name'] }}">0</output>
                                <button type="button" data-cart-change="1" aria-label="Tambah {{ $product['display_name'] }}">+</button>
                            </div>
                            <strong class="sc-subtotal" data-cart-subtotal></strong>
                            <button class="sc-remove" type="button" data-cart-remove aria-label="Hapus {{ $product['display_name'] }}" title="Hapus produk">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg>
                            </button>
                        </article>
                    @endforeach
                </section>
                <aside class="sc-summary" aria-label="Ringkasan belanja">
                    <p class="sc-summary__eyebrow">Ringkasan</p><h2>Belanja Anda</h2>
                    <dl><div><dt>Jumlah barang</dt><dd data-summary-count>0</dd></div><div><dt>Subtotal produk</dt><dd data-summary-subtotal>Rp 0</dd></div></dl>
                    <div class="sc-total"><span>Total</span><strong data-cart-total>Rp 0</strong></div>
                    <button class="sc-primary" type="button" disabled>Lanjut pemesanan</button>
                    {{-- TODO: sambungkan ke alur pemesanan resmi setelah backend tersedia. --}}
                    <p class="sc-summary__note">Pemesanan belum tersedia. Barang di keranjang belum menjadi pesanan.</p>
                </aside>
            </div>
        @endif
        <p class="sc-status" role="status" aria-live="polite" data-cart-status></p>
    </main>
@endsection
