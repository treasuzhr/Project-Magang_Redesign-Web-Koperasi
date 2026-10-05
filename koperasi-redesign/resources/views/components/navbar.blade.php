<header class="site-header">
    <div class="navbar page-width">
        <a class="brand" href="#beranda" aria-label="Primkop Pullahta Hankam, beranda">
            <img src="{{ asset('assets/images/logo/Logo PRIMKOP.png') }}" alt="Primkop Pullahta Hankam">
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Buka menu navigasi">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links" id="primary-navigation" aria-label="Navigasi utama">
            <a class="nav-link nav-link--active" href="#beranda" aria-current="page">Beranda</a>
            <a class="nav-link" href="#footer">Tentang</a>
            <a class="nav-link" href="#kategori">Kategori</a>
            <a class="nav-link" href="#berita">Berita</a>
        </nav>
        <div class="nav-actions">
            <a class="cart-link" href="#kategori" aria-label="Jelajahi kategori produk">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 8H6M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
            </a>
            <a class="button button--login" href="{{ url('/login') }}">Masuk</a>
        </div>
    </div>
</header>
