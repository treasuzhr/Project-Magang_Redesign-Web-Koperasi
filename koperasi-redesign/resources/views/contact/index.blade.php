@extends('layouts.public')

@section('title', 'Kontak | Primkop Pullahta Hankam')
@section('description', 'Alamat, telepon, dan jam layanan Primkop Pullahta Hankam di lingkungan Pusdatin Kemhan.')

@section('content')
    <main class="contact-page">
        <div class="page-width">
            <header class="contact-intro">
                <p class="section-eyebrow">Kontak Primkop</p>
                <h1>Mari terhubung,<br><span>kami siap membantu.</span></h1>
                <p>Temukan informasi untuk menghubungi koperasi atau berkunjung langsung ke Primkop Pullahta Hankam.</p>
            </header>

            <section class="contact-grid" aria-label="Informasi kontak dan lokasi">
                <article class="contact-location" data-contact-map>
                    <iframe class="contact-location__map" src="https://www.google.com/maps?q=-6.3131641,106.7922979&amp;z=17&amp;output=embed" title="Peta lokasi Pusdatin Kemhan" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen tabindex="-1"></iframe>
                    <div class="contact-location__overlay">
                    <span class="contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <p class="section-eyebrow">Kunjungi kami</p>
                    <h2>Dekat dengan<br>keseharian anggota.</h2>
                    <address>Jalan RS Fatmawati Nomor 1, RT 006/RW 006,<br>Pondok Labu, Cilandak, Jakarta Selatan,<br>DKI Jakarta 12450</address>
                    <button class="button button--primary contact-location__activate" type="button" data-map-activate aria-expanded="false">Jelajahi peta</button>
                    </div>
                    <button class="contact-location__close" type="button" data-map-close hidden>Tutup peta ×</button>
                    <a class="contact-location__maps-link" href="https://maps.app.goo.gl/W6NsazYSRR4Tihu58" target="_blank" rel="noopener noreferrer">Buka di Google Maps ↗</a>
                </article>

                <div class="contact-details">
                    <article class="contact-card">
                        <span class="contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m7 3 3 5-2 2a16 16 0 0 0 6 6l2-2 5 3-1 4C10 22 2 14 3 4Z"/></svg>
                        </span>
                        <div>
                            <h2>Telepon</h2>
                            <p class="contact-card__value">3215</p>
                            <p>Hubungi koperasi pada jam layanan.</p>
                        </div>
                    </article>
                    <article class="contact-card">
                        <span class="contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <div>
                            <h2>Jam layanan</h2>
                            <p class="contact-card__value">07.30–15.00 WIB</p>
                            <p>Informasi jam layanan mengacu pada website sebelumnya.</p>
                        </div>
                    </article>
                    <p class="contact-note">Untuk pertanyaan seputar keanggotaan, simpanan, pinjaman, atau pesanan, silakan hubungi atau kunjungi koperasi.</p>
                </div>
            </section>
        </div>
    </main>
@endsection
