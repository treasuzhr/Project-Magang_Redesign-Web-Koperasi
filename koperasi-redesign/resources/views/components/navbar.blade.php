<header class="site-header">
    <div class="navbar page-width">
        <a class="brand" href="{{ route('home') }}#beranda" aria-label="Primkop Pullahta Hankam, beranda">
            <img src="{{ asset('assets/images/logo/Logo PRIMKOP.png') }}" alt="Primkop Pullahta Hankam">
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Buka menu navigasi">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links" id="primary-navigation" aria-label="Navigasi utama">
            <a class="nav-link {{ request()->routeIs('home') ? 'nav-link--active' : '' }}"
                href="{{ route('home') }}#beranda" @if (request()->routeIs('home')) aria-current="page" @endif>Beranda</a>
            <a class="nav-link {{ request()->routeIs('about') ? 'nav-link--active' : '' }}"
                href="{{ route('about') }}" @if (request()->routeIs('about')) aria-current="page" @endif>Tentang</a>
            <a class="nav-link" href="{{ route('home') }}#berita">Berita</a>
            <a class="nav-link {{ request()->routeIs('products.index') ? 'nav-link--active' : '' }}"
                href="{{ route('products.index') }}" @if (request()->routeIs('products.index')) aria-current="page" @endif>Produk</a>
        </nav>
        <form class="nav-search" method="GET" action="{{ route('products.index') }}" role="search" aria-label="Cari produk">
            <button type="submit" aria-label="Cari produk">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4.2 4.2"/></svg>
            </button>
            <input
                type="search"
                name="q"
                value="{{ request()->query('q', '') }}"
                placeholder="Cari produk..."
                aria-label="Cari produk"
                maxlength="100"
            >
        </form>
        <div class="nav-actions">
            <a class="cart-link" href="{{ route('products.index') }}" aria-label="Jelajahi produk">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 8H6M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
            </a>
            <a class="button button--login" href="{{ url('/login') }}">Masuk</a>
        </div>
    </div>
</header>
