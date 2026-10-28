@extends('layouts.public')

@section('title', 'Tentang Kami | Primkop Pullahta Hankam')
@section('description', 'Profil dan Struktur Pengurus Koperasi Primkop Pullahta Hankam.')

@section('content')
    @php
        $flattenPeople = function (array $person) use (&$flattenPeople): array {
            return [$person, ...array_merge([], ...array_map($flattenPeople, $person['children']))];
        };
        $people = collect($flattenPeople($organization));
        $peopleByName = $people->keyBy('name');
        $groups = [
            'pembina' => ['title' => 'Pembina'],
            'pengurus' => ['title' => 'Pengurus dan Pengawas'],
            'unit' => ['title' => 'Kepala Unit'],
        ];
    @endphp

    <main class="about-page about-editorial" data-about-page>
        <div class="page-width">
            <section class="about-overview" aria-label="Profil koperasi">
                <header class="about-intro">
                    <p class="section-eyebrow home-reveal">Tentang Kami</p>
                    <h1 data-about-heading>Primkop Pullahta <span class="about-title-accent">Hankam</span></h1>
                    <div class="home-reveal">
                        <p>Koperasi Biro Pengumpulan dan Pengolahan Data Setjen Dephankam didirikan pada tanggal
                            23 Januari 1989 dan beralamatkan di Jl. RS. Fatmawati No. 1 Pondok Labu Cilandak
                            Kota Jakarta Selatan Daerah Khusus Ibukota Jakarta.</p>
                        <p>Sektor Usaha Jasa Keuangan dan Asuransi. Saat ini Koperasi memiliki Status Grade A.</p>
                    </div>
                </header>
                <figure class="about-portrait" data-about-visual>
                    <div class="about-portrait__crop" data-about-photo-reveal>
                        <img src="{{ asset('assets/images/news/berita-2.png') }}" alt="Toko Def.MART, unit usaha Primkop Pullahta Hankam" fetchpriority="high">
                    </div>

                    <figcaption>Def.MART </figcaption>
                </figure>
                <dl class="about-facts home-reveal" aria-label="Informasi koperasi">
                    <div class="about-fact"><dt>Didirikan</dt><dd>23 Jan <span data-count-up data-count-start="1900" data-count-end="1989" aria-label="1989">1989</span></dd></div>
                    <div class="about-fact"><dt>Status</dt><dd>Grade <span>A</span></dd></div>
                    <div class="about-fact"><dt>Jam Kerja</dt><dd>07:30 – 15:00</dd></div>
                    <div class="about-fact"><dt>Telepon</dt><dd><a href="tel:3215"><span data-count-up data-count-start="3000" data-count-end="3215" aria-label="3215">3215</span></a></dd></div>
                </dl>
            </section>

            <section class="organization-section" aria-labelledby="organization-title" data-organization>
                <div class="organization-heading about-section-heading"><p class="section-eyebrow">Orang di balik Primkop</p><h2 id="organization-title" data-about-heading>Struktur Pengurus Koperasi</h2><p>Susunan pengurus dan hubungan pelaporan Primkop Pullahta Hankam.</p></div>
                <div class="about-filters" role="group" aria-label="Filter pengurus" hidden>
                    @foreach (['all' => 'Semua', 'pembina' => 'Pembina', 'pengurus' => 'Pengurus', 'pengawas' => 'Pengawas', 'unit' => 'Kepala Unit'] as $value => $label)
                        <button type="button" data-organization-filter="{{ $value }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <p class="about-filter-status sr-only" data-organization-status role="status" aria-live="polite">13 pengurus ditampilkan.</p>
                @foreach ($groups as $groupKey => $group)
                    <section class="about-people-group" data-people-group="{{ $groupKey }}" aria-labelledby="group-{{ $groupKey }}">
                        <header><div><h3 id="group-{{ $groupKey }}">{{ $group['title'] }}</h3></div><span class="about-swipe-hint" aria-hidden="true">Geser untuk melihat ↔</span></header>
                        <ul class="about-people-grid about-people-grid--{{ $groupKey }}">
                            @foreach ($people as $person)
                                @php
                                    $personGroup = str_starts_with($person['position'], 'Pembina') ? 'pembina' : (str_starts_with($person['position'], 'Ka Unit') ? 'unit' : 'pengurus');
                                    $parentId = $person['reports_to'] ? $peopleByName->get($person['reports_to'])['id'] : null;
                                @endphp
                                @if ($personGroup === $groupKey)
                                    @include('components.organization-node', ['person' => $person, 'group' => $personGroup, 'parentId' => $parentId])
                                @endif
                            @endforeach
                        </ul>
                    </section>
                @endforeach
                <dialog class="about-person-dialog" id="about-person-dialog" data-person-dialog aria-labelledby="person-dialog-name">
                    <button type="button" class="about-person-dialog__close" data-dialog-close aria-label="Tutup detail pengurus">×</button>
                    <div class="about-person-dialog__photo"><img data-dialog-photo alt="" hidden><span data-dialog-initials aria-hidden="true"></span></div>
                    <div class="about-person-dialog__copy"><p class="section-eyebrow">Pengurus Primkop</p><h2 id="person-dialog-name"></h2><p data-dialog-position></p><div data-dialog-report></div></div>
                </dialog>
            </section>

            <section class="about-contact home-reveal" data-cursor-glow aria-labelledby="about-contact-title">
                <div><p class="section-eyebrow">Mari terhubung</p><h2 id="about-contact-title" data-about-heading>Ada pertanyaan seputar keanggotaan?</h2></div>
                <div class="about-contact__actions"><a class="button button--primary" href="tel:3215">Hubungi 3215</a><button class="button about-contact__copy" type="button" data-copy-phone hidden>Salin nomor</button><span class="sr-only" role="status" data-copy-status></span></div>
            </section>
        </div>
    </main>
@endsection
