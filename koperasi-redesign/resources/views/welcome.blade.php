@extends('layouts.public')

@section('content')
    <main>
        <section class="hero" id="beranda" aria-labelledby="hero-title">
            <div class="hero__image" aria-hidden="true">
                <img src="{{ asset('assets/images/hero/hero-utama.jpg') }}" alt="">
            </div>
            <div class="hero__content page-width">
                <p class="eyebrow-pill"><span></span> Platform Resmi Koperasi Kemhan</p>
                <h1 id="hero-title">Produk Koperasi<br><span>Primkop Pullahta</span><br>Hankam</h1>
                <p class="hero__description">Tersedia dalam platform web. Lihat stok produk dan lakukan pemesanan kapan saja, di mana saja.</p>
                <div class="hero__actions">
                    <a class="button button--primary" href="{{ route('products.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 8H6M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                        Belanja Sekarang
                    </a>
                    <a class="button button--secondary" href="{{ route('about') }}">Tentang Kami</a>
                </div>
            </div>
        </section>

        <section class="stats" aria-label="Statistik koperasi">
            <div class="stats__inner page-width">
                <div class="stat"><strong>160+</strong><span>Anggota Aktif</span></div>
                <div class="stat"><strong>200+</strong><span>Produk Tersedia</span></div>
                <div class="stat"><strong>1989</strong><span>Tahun Berdiri</span></div>
                <div class="stat"><strong>9</strong><span>Kategori Produk</span></div>
            </div>
        </section>

        <section class="categories section page-width" id="kategori" aria-labelledby="categories-title">
            <x-section-heading eyebrow="Telusuri" title="Kategori Produk" id="categories-title" />

            <div class="category-carousel" role="region" aria-roledescription="carousel" aria-label="Kategori produk">
                <div class="category-track" id="category-track" tabindex="0" aria-label="Kategori produk">
                    @foreach ($categories as $category)
                        <x-category-card :category="$category" />
                    @endforeach
                </div>
                <div class="category-carousel__controls">
                    <div class="category-carousel__dots" aria-label="Pilih posisi kategori"></div>
                    <div class="category-carousel__arrows">
                        <button class="carousel-arrow" type="button" data-category-direction="previous" aria-label="Kategori sebelumnya" aria-controls="category-track">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button class="carousel-arrow carousel-arrow--next" type="button" data-category-direction="next" aria-label="Kategori berikutnya" aria-controls="category-track">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="news-section section" id="berita" aria-labelledby="news-title">
            <div class="page-width">
                <x-section-heading eyebrow="Terkini" title="Berita & Informasi" id="news-title">
                    <a class="text-link" href="#berita">Semua berita <span aria-hidden="true">→</span></a>
                </x-section-heading>
                <div class="news-grid">
                    @foreach ($news as $article)
                        <x-news-card :article="$article" />
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
