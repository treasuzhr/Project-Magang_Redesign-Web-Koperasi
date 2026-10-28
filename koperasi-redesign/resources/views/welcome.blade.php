@extends('layouts.public')

@section('content')
    <main class="home-page">
        @php
            $featuredNews = $news[0] ?? null;
            $supportingNews = array_slice($news, 1, 2);
            $featuredNewsUrl = $featuredNews && isset($featuredNews['slug'])
                ? route('news.show', $featuredNews['slug'])
                : route('news.index');
        @endphp

        <section class="hero home-hero" id="beranda" aria-labelledby="hero-title">
            <div class="hero__image" aria-hidden="true">
                <img src="{{ asset('assets/images/hero/hero-utama.jpg') }}" alt="">
            </div>
            <div class="hero__content page-width">
                <p class="eyebrow-pill"><span></span> Koperasi Pusdatin Kemhan · Berdiri sejak 1989</p>
                <h1 id="hero-title">Primkop Pullahta<br><span>Hankam</span></h1>
                <p class="hero__description">Mengenal koperasi yang melayani anggota di lingkungan Pusdatin Kemhan.</p>
                <div class="hero__actions">
                    <a class="button button--primary" href="#kategori">
                        Jelajahi kategori
                    </a>
                    <a class="button button--secondary" href="{{ route('about') }}">Tentang Primkop</a>
                </div>
            </div>
        </section>

        <section class="home-facts" id="kilas" aria-label="Sekilas Primkop">
            <div class="home-facts__inner page-width">
                <div class="home-facts__lead">
                    <strong data-count-up data-count-start="1900" data-count-end="1989" aria-label="1989">1989</strong>
                    <span>Tahun berdiri</span>
                </div>
                <p><strong><span data-count-up data-count-start="0" data-count-end="160" aria-label="160">160</span>+</strong><span>anggota aktif per 31 Desember 2024</span></p>
                <p><strong>Grade A</strong><span>Status koperasi yang tercantum pada profil</span></p>
            </div>
        </section>

        <section class="home-services section" id="layanan-anggota" aria-labelledby="services-title">
            <div class="page-width">
                <div class="home-section-intro home-section-intro--services home-reveal">
                    <div>
                        <p class="section-eyebrow">Untuk anggota</p>
                        <h2 id="services-title">Satu situs,<br><span>empat layanan.</span></h2>
                    </div>
                </div>

                <div class="service-stack">
                    <x-landing.service-card
                        number="0"
                        theme="savings"
                        title="Simpanan yang bisa Anda pantau"
                        description="Pokok, Wajib, Sukarela, dan Khusus. Lihat saldo masing-masing dan setor atau tarik simpanan sukarela kapan perlu."
                        button="Masuk anggota"
                        href="{{ url('/login') }}"
                    >
                        <div class="service-visual service-visual--savings" aria-label="Ilustrasi jenis simpanan">
                            <div class="savings-bar" aria-hidden="true">
                                <span style="--w: 61.9%; --segment-color: #2fa37a"></span>
                                <span style="--w: 19%; --segment-color: #7fad39"></span>
                                <span style="--w: 17.9%; --segment-color: #f0c23b"></span>
                                <span style="--w: 1.2%; --segment-color: #4a9cc4"></span>
                            </div>
                            <div class="savings-legend">
                                <span><i style="--segment-color: #2fa37a"></i>Wajib</span>
                                <span><i style="--segment-color: #7fad39"></i>Sukarela</span>
                                <span><i style="--segment-color: #f0c23b"></i>Khusus</span>
                                <span><i style="--segment-color: #4a9cc4"></i>Pokok</span>
                            </div>
                        </div>
                    </x-landing.service-card>

                    <x-landing.service-card
                        number="1"
                        theme="loan"
                        title="Pinjaman dengan simulasi di depan"
                        description="Geser jumlah dan tenor, lihat angsuran per bulan, baru ajukan."
                        button="Coba simulasinya"
                        href="{{ url('/login') }}"
                    >
                        <div class="service-visual service-visual--loan" aria-label="Contoh simulasi pinjaman">
                            <div class="loan-panel">
                                <span>Total pinjaman</span>
                                <strong>Rp 10.000.000</strong>
                                <div class="loan-slider" aria-hidden="true"><span></span><i></i></div>
                                <span>Tenor <strong>12 bulan</strong></span>
                                <div class="loan-result">
                                    <span>Contoh angsuran</span>
                                    <strong>Rp <span data-service-counter data-count-end="933333">0</span></strong>
                                </div>
                            </div>
                        </div>
                    </x-landing.service-card>

                    <x-landing.service-card
                        number="2"
                        theme="shu"
                        title="SHU dari tahun ke tahun"
                        description="Grafik Sisa Hasil Usaha Anda per tahun buku. Arahkan kursor ke batang untuk melihat informasinya."
                        button="Lihat SHU"
                        href="{{ url('/login') }}"
                    >
                        <div class="service-visual service-visual--shu" aria-label="Ilustrasi grafik Sisa Hasil Usaha">
                            <div class="shu-chart">
                                <span tabindex="0" role="img" aria-label="Ilustrasi grafik SHU, angka tersedia setelah masuk anggota" data-tooltip="SHU anggota" title="Data SHU tampil setelah masuk anggota" style="--bar-height: 60%; --bar-delay: 0ms"></span>
                                <span tabindex="0" role="img" aria-label="Ilustrasi grafik SHU, angka tersedia setelah masuk anggota" data-tooltip="SHU anggota" title="Data SHU tampil setelah masuk anggota" style="--bar-height: 36%; --bar-delay: 90ms"></span>
                                <span tabindex="0" role="img" aria-label="Ilustrasi grafik SHU, angka tersedia setelah masuk anggota" data-tooltip="SHU anggota" title="Data SHU tampil setelah masuk anggota" style="--bar-height: 62%; --bar-delay: 180ms"></span>
                                <span tabindex="0" role="img" aria-label="Ilustrasi grafik SHU, angka tersedia setelah masuk anggota" data-tooltip="SHU anggota" title="Data SHU tampil setelah masuk anggota" style="--bar-height: 91%; --bar-delay: 270ms"></span>
                                <span tabindex="0" role="img" aria-label="Ilustrasi grafik SHU, angka tersedia setelah masuk anggota" data-tooltip="SHU anggota" title="Data SHU tampil setelah masuk anggota" style="--bar-height: 100%; --bar-delay: 360ms"></span>
                            </div>
                        </div>
                    </x-landing.service-card>

                    <x-landing.service-card
                        number="3"
                        theme="shop"
                        title="Belanja dari meja kerja"
                        description="Pilih produk, masukkan ke keranjang, dan pesan. Jelajahi kebutuhan di katalog Def.MART."
                        button="Lihat semua produk"
                        href="{{ route('products.index') }}"
                    >
                        <div class="service-visual service-visual--shop" aria-label="Pratinjau katalog produk">
                            <div class="shop-preview">
                                <span class="shop-preview__eyebrow">Def.MART · Katalog</span>
                                <div><i aria-hidden="true"></i><span>Kebutuhan kantor</span></div>
                                <div><i aria-hidden="true"></i><span>Kebutuhan harian</span></div>
                                <a href="{{ route('products.index') }}">Jelajahi produk</a>
                            </div>
                        </div>
                    </x-landing.service-card>
                </div>
            </div>
        </section>

        <x-landing.category-showcase :categories="$categories" />

        <section class="home-about" aria-labelledby="home-about-title">
            <div class="home-about__inner page-width home-reveal">
                <div class="home-about__visual" data-about-visual>
                    <img src="{{ asset('assets/images/news/berita-2.png') }}" alt="Papan nama Def.MART, unit toko Primkop Pullahta Hankam" loading="lazy">
                    <span class="home-about__caption">Unit toko · Def.MART</span>
                </div>
                <div class="home-about__copy" data-about-copy>
                    <p class="section-eyebrow home-about__stagger">Tentang Primkop</p>
                    <h2 id="home-about-title" class="home-about__stagger">Tumbuh bersama<br><span>kebutuhan anggota.</span></h2>
                    <p class="home-about__stagger">
                        Berdiri pada 23 Januari 1989, Primkop Pullahta Hankam melayani anggota aktif di lingkungan Pusdatin Kemhan.
                        Unit tokonya, Def.MART, menyediakan kebutuhan kantor dan kebutuhan harian.
                    </p>
                    <a class="button button--primary home-about__stagger" href="{{ route('about') }}">Kenali Primkop lebih dekat</a>
                </div>
            </div>
        </section>

        <section class="home-news section page-width" id="berita" aria-labelledby="news-title">
            <div class="home-section-intro home-section-intro--news home-reveal">
                <div>
                    <p class="section-eyebrow">Kabar Primkop</p>
                    <h2 id="news-title">Cerita dan informasi<br><span>terbaru.</span></h2>
                </div>
                <a class="home-text-link" href="{{ route('news.index') }}">Semua berita</a>
            </div>

            @if ($featuredNews)
                @php
                    $featuredTitleWords = preg_split('/\s+/', trim($featuredNews['title'] ?? ''));
                    $featuredTitleLines = [];

                    if ($featuredTitleWords) {
                        $featuredTitleSplit = (int) ceil(count($featuredTitleWords) / 2);
                        $featuredTitleLines[] = implode(' ', array_slice($featuredTitleWords, 0, $featuredTitleSplit));
                        if ($featuredTitleSplit < count($featuredTitleWords)) {
                            $featuredTitleLines[] = implode(' ', array_slice($featuredTitleWords, $featuredTitleSplit));
                        }
                    }
                @endphp
                <div class="home-news__layout">
                    <article class="home-news-feature" data-news-feature>
                        <div class="home-news-feature__image" data-news-cursor>
                            <img src="{{ asset($featuredNews['image']) }}" alt="" loading="lazy">
                            <span class="home-news-feature__cursor" aria-hidden="true">Baca</span>
                        </div>
                        <div class="home-news-feature__copy">
                            <div class="home-news-feature__meta">
                                <span class="home-news-feature__latest">Terbaru</span>
                                <time datetime="{{ $featuredNews['datetime'] ?? '' }}">{{ $featuredNews['date'] ?? '' }}</time>
                            </div>
                            <h3 class="home-news-feature__title">
                                <a href="{{ $featuredNewsUrl }}">
                                    @foreach ($featuredTitleLines as $line)
                                        <span>{{ $line }}</span>
                                    @endforeach
                                </a>
                            </h3>
                            <p>{{ $featuredNews['excerpt'] }}</p>
                            <a class="home-news-feature__link" href="{{ $featuredNewsUrl }}">Baca ceritanya</a>
                        </div>
                    </article>

                    @if ($supportingNews)
                        <aside class="home-news-list" aria-label="Berita lainnya">
                            @foreach ($supportingNews as $index => $article)
                                <x-landing.news-card :article="$article" :index="$index" />
                            @endforeach
                        </aside>
                    @endif
                </div>
            @endif
        </section>

        <section class="home-join page-width" data-cursor-glow aria-labelledby="home-join-title">
            <div class="home-join__orbs" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="home-join__copy">
                <p class="section-eyebrow">Layanan anggota</p>
                <h2 id="home-join-title">Sudah jadi anggota?<br><span>Masuk dan lihat buku simpanan Anda.</span></h2>
            </div>
            <a class="button button--primary home-join__button" href="{{ url('/login') }}">Masuk anggota</a>
            <span class="home-join__light" aria-hidden="true"></span>
        </section>
    </main>
@endsection
