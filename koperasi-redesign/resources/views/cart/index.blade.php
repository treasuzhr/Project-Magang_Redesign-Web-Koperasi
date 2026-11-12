@extends('layouts.public')
@section('title', 'Keranjang | Primkop Pullahta Hankam')
@section('description', 'Lihat dan atur produk di keranjang Anda.')
@section('content')
    @vite('resources/js/cart.js')
    <main class="shopping-cart" data-shopping-cart data-products-url="{{ route('cart.products') }}">
        <header class="sc-header">
            <div><p class="section-eyebrow">Pilihan Anda</p><h1>Keranjang <span class="sc-title-accent">belanja</span></h1><p>Periksa kembali produk dan jumlahnya.</p></div>
            <a class="sc-back" href="{{ route('products.index') }}">Lanjut belanja <span aria-hidden="true">&rarr;</span></a>
        </header>
        @if ($errors->any())
            <div class="sc-notice" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <p data-cart-loading role="status">Memuat keranjang&hellip;</p>
        <noscript><p class="sc-notice">Aktifkan JavaScript untuk melihat dan mengatur keranjang.</p></noscript>
        <p class="sc-notice" data-storage-notice hidden>Keranjang hanya tersimpan selama halaman ini terbuka.</p>
        <p class="sc-notice" data-cart-error hidden>Keranjang belum dapat dimuat. Coba muat ulang halaman.</p>
            <section class="sc-empty" data-cart-empty hidden>
                <div class="sc-empty__icon"><x-catalog-icon name="cart" /></div>
                <h2>Keranjang masih kosong</h2><p>Produk yang Anda tambahkan akan muncul di sini.</p>
                <a class="sc-primary" href="{{ route('products.index') }}">Lihat produk</a>
            </section>
            <div class="sc-layout" data-cart-filled hidden>
                <section class="sc-items" aria-label="Produk di keranjang">
                    <div class="sc-items__heading"><h2>Produk Anda</h2><span data-cart-count></span></div>
                    <div class="sc-columns" aria-hidden="true"><span>Produk</span><span>Jumlah</span><span>Subtotal</span><span></span></div>
                    <div data-cart-items></div>
                </section>
                <aside class="sc-summary" aria-label="Ringkasan belanja">
                    <p class="sc-summary__eyebrow">Ringkasan</p><h2>Belanja Anda</h2>
                    <dl><div><dt>Jumlah barang</dt><dd data-summary-count>0</dd></div><div><dt>Subtotal produk</dt><dd data-summary-subtotal>Rp 0</dd></div></dl>
                    <div class="sc-total"><span>Total</span><strong data-cart-total>Rp 0</strong></div>
                    @if ($checkout)
                        <p>Ambil sendiri di Def.MART. Jam kerja: 07:30 sampai 15:00.</p>
                        <p>{{ $paymentNotice }}</p>
                        <form method="POST" action="{{ route('checkout.store') }}" data-checkout-form>
                            @csrf
                            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
                            <div data-checkout-items></div>
                            <label for="checkout-note">Catatan (opsional)</label>
                            <textarea id="checkout-note" name="catatan" maxlength="1000">{{ is_scalar(old('catatan')) ? old('catatan') : '' }}</textarea>
                            <button class="sc-primary" type="submit" data-checkout-submit disabled>Buat pesanan</button>
                        </form>
                    @else
                        <a class="sc-primary" href="{{ route('checkout.show') }}">Lanjut pemesanan</a>
                    @endif
                </aside>
            </div>
        <p class="sc-status" role="status" aria-live="polite" data-cart-status></p>
    </main>
@endsection
