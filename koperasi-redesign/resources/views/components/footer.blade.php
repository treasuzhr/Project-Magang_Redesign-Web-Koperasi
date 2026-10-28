<footer class="site-footer" id="footer">
    <div class="footer-main page-width">
        <div class="footer-about">
            <a class="brand brand--footer" href="{{ route('home') }}#beranda" aria-label="Primkop Pullahta Hankam, beranda">
                <img src="{{ asset('assets/images/logo/Logo PRIMKOP.png') }}" alt="Primkop Pullahta Hankam">
            </a>
            <p>Melayani anggota dengan semangat kebersamaan dan gotong royong demi kesejahteraan bersama.</p>
            <address id="kontak">
                <span class="footer-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                </span>
                Jalan RS Fatmawati Nomor 1, RT 006/RW 006,<br>Pondok Labu, Cilandak, Jakarta Selatan 12450
            </address>
            <a class="footer-phone" href="tel:3215"><span class="footer-icon" aria-hidden="true">☎</span> Telepon: 3215</a>
        </div>
        <div class="footer-column" data-footer-reveal>
            <h2>Navigasi</h2>
            <a href="{{ route('home') }}#beranda">Beranda</a>
            <a href="{{ route('about') }}">Tentang Kami</a>
            <a href="{{ route('home') }}#kategori">Kategori Produk</a>
            <a href="{{ route('news.index') }}">Berita</a>
            <a href="{{ route('contact') }}">Kontak</a>
        </div>
        <div class="footer-column" data-footer-reveal>
            <h2>Layanan Anggota</h2>
            <span>Dashboard</span>
            <span>Simpanan</span>
            <span>Pinjaman</span>
            <span>SHU</span>
            <span>Data Order</span>
            <span>Pengajuan Pinjaman</span>
        </div>
    </div>
    <div class="footer-bottom page-width">
        <p>2021 © Pusdatin, Kementerian Pertahanan Republik Indonesia</p>
        <div><a href="#footer">Kebijakan Privasi</a><a href="#footer">Syarat &amp; Ketentuan</a></div>
    </div>
</footer>
