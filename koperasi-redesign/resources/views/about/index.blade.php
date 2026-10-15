@extends('layouts.public')

@section('title', 'Tentang Kami | Primkop Pullahta Hankam')

@section('content')
    <main class="about-page">
        <div class="page-width">
            <section class="about-overview" aria-label="Profil koperasi">
                <header class="about-intro">
                    <p class="section-eyebrow">Tentang Kami</p>
                    <h1>Primkop Pullahta Hankam</h1>
                    <p>
                        Koperasi Biro Pengumpulan dan Pengolahan Data Setjen Dephankam didirikan pada tanggal
                        23 Januari 1989 dan beralamatkan di Jl. RS. Fatmawati No. 1 Pondok Labu Cilandak
                        Kota Jakarta Selatan Daerah Khusus Ibukota Jakarta.
                    </p>
                    <p>
                        Sektor Usaha Jasa Keuangan dan Asuransi. Saat ini Koperasi memiliki Status Grade A
                        dan melayani seluruh anggota aktif di lingkungan Pusdatin Kemhan.
                    </p>
                </header>

                <dl class="about-facts" aria-label="Informasi koperasi">
                    <div class="about-fact">
                        <dt>Didirikan</dt>
                        <dd>23 Jan 1989</dd>
                    </div>
                    <div class="about-fact">
                        <dt>Status</dt>
                        <dd>Grade A</dd>
                    </div>
                    <div class="about-fact">
                        <dt>Jam Kerja</dt>
                        <dd>07:30 – 15:00</dd>
                    </div>
                    <div class="about-fact">
                        <dt>Telepon</dt>
                        <dd>3215</dd>
                    </div>
                </dl>
            </section>

            <section class="about-values" aria-label="Visi dan misi">
                <article class="about-value about-value--vision">
                    <span class="about-value__icon" aria-hidden="true">◎</span>
                    <h2>Visi</h2>
                    <p>
                        Menjadi koperasi yang mandiri, modern, dan terpercaya dalam meningkatkan
                        kesejahteraan anggota di lingkungan Pusdatin Kemhan.
                    </p>
                </article>
                <article class="about-value about-value--mission">
                    <span class="about-value__icon" aria-hidden="true">✓</span>
                    <h2>Misi</h2>
                    <ol>
                        <li>Menyediakan produk kebutuhan dengan harga terjangkau</li>
                        <li>Mengelola simpan pinjam secara profesional dan transparan</li>
                        <li>Meningkatkan SHU anggota secara berkelanjutan</li>
                        <li>Memperkuat solidaritas dan gotong royong</li>
                    </ol>
                </article>
            </section>
        </div>

        <section class="organization-section page-width" aria-labelledby="organization-title">
            <div class="organization-heading">
                <p class="section-eyebrow">Tentang Kami</p>
                <h2 id="organization-title">Struktur Pengurus Koperasi</h2>
                <p>Susunan pengurus dan hubungan pelaporan Primkop Pullahta Hankam.</p>
            </div>

            <div class="org-chart-frame">
                <div class="org-chart__viewport" tabindex="0" aria-label="Bagan struktur pengurus; dapat digulir secara horizontal pada layar sempit">
                    <ul class="org-chart__tree" aria-label="Struktur organisasi koperasi">
                        @include('components.organization-node', ['person' => $organization, 'depth' => 0])
                    </ul>
                </div>
            </div>
        </section>
    </main>
@endsection
