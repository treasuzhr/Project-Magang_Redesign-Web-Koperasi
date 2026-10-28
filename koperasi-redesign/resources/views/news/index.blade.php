@extends('layouts.public')

@section('title', 'Berita | Primkop Pullahta Hankam')
@section('description', 'Berita dan informasi terbaru dari Primkop Pullahta Hankam, Pusdatin Kemhan.')

@section('content')
    <div class="news-reading-progress" data-news-progress aria-hidden="true"></div>
    <main class="news-page news-editorial" data-news-page>
        @if ($featured)
            <section class="news-feature page-width" aria-labelledby="news-feature-title" data-news-hero data-news-slug="{{ $featured['slug'] }}">
                <div class="news-feature__clip">
                    <a class="news-feature__media" href="{{ route('news.show', $featured['slug']) }}" aria-label="Baca {{ $featured['title'] }}">
                        <img class="news-feature__image" src="{{ asset($featured['image']) }}" alt="" fetchpriority="high">
                    </a>
                </div>
                <div class="news-feature__content">
                    <div class="news-feature__meta" data-reveal style="--d: 500"><span class="news-feature__badge">TERBARU</span><time datetime="{{ $featured['datetime'] }}">{{ $featured['date'] }}</time></div>
                    <h1 id="news-feature-title" data-words style="--d: 600">{{ $featured['title'] }}</h1>
                    <p data-reveal style="--d: 900">{{ $featured['summary'] }}</p>
                    <div class="news-feature__action" data-reveal style="--d: 1050"><a class="news-feature__link" href="{{ route('news.show', $featured['slug']) }}">Baca selengkapnya</a></div>
                </div>
            </section>
        @else
            <div class="page-width"><h1>Berita</h1><p>Belum ada berita.</p></div>
        @endif

        @if ($articles->isNotEmpty())
            <section class="news-listing page-width" id="news-list" aria-labelledby="news-list-title">
                <h2 id="news-list-title" data-words>Berita <span>lainnya</span></h2>
                <div class="news-page__grid {{ $articles->count() === 2 ? 'news-page__grid--pair' : '' }}" data-stagger="140">
                    @foreach ($articles as $article)
                        <x-news-card :article="$article" :index="$loop->index" />
                    @endforeach
                </div>
                @if ($articles->hasPages())
                    <nav class="news-pagination" aria-label="Halaman berita">
                        @if ($articles->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $articles->previousPageUrl() }}#news-list">Sebelumnya</a>@endif
                        <span aria-current="page">{{ $articles->currentPage() }} / {{ $articles->lastPage() }}</span>
                        @if ($articles->hasMorePages())<a href="{{ $articles->nextPageUrl() }}#news-list">Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
                    </nav>
                @endif
            </section>
        @endif
    </main>
@endsection
