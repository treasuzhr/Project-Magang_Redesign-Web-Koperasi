@extends('layouts.public')

@section('title', 'Berita | Primkop Pullahta Hankam')
@section('description', 'Berita dan informasi terbaru dari Primkop Pullahta Hankam, Pusdatin Kemhan.')

@section('content')
    <main class="news-page">
        <section class="news-feature page-width" aria-labelledby="news-feature-title">
            <img class="news-feature__image" src="{{ asset($featured['image']) }}" alt="">
            <div class="news-feature__content">
                <span class="news-feature__badge">TERKINI</span>
                <h1 id="news-feature-title">{{ $featured['title'] }}</h1>
                <p>{{ $featured['excerpt'] }}</p>
                <a class="news-feature__link" href="#news-list">
                    Baca Selengkapnya <span aria-hidden="true">›</span>
                </a>
            </div>
        </section>

        <section class="news-listing page-width" id="news-list" aria-labelledby="news-list-title">
            <p class="section-eyebrow">TERKINI</p>
            <h2 id="news-list-title">Berita &amp; Informasi</h2>
            <div class="news-page__grid">
                @foreach ($articles as $article)
                    <x-news-card :article="$article" />
                @endforeach
            </div>
        </section>
    </main>
@endsection
