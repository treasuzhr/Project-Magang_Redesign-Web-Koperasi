@extends('layouts.public')

@section('title', $article['title'] . ' | Berita Primkop Pullahta Hankam')
@section('description', $article['excerpt'])

@section('content')
    <div class="news-reading-progress" data-news-progress aria-hidden="true"></div>
    <main class="news-article-page news-detail page-enter" data-news-page>
        <article class="news-article page-width">
            <nav class="news-article__breadcrumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('news.index') }}">Berita</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $article['title'] }}</span>
            </nav>

            <header class="news-article__header">
                <p class="news-article__eyebrow">BERITA</p>
                <h1 data-words>{{ $article['title'] }}</h1>
                <div class="news-article__meta">
                    <time datetime="{{ $article['datetime'] }}">{{ $article['date'] }}</time>
                    <span class="news-article__meta-divider" aria-hidden="true"></span>
                    <span>{{ $article['reading_minutes'] }} menit baca</span>
                    @if (! empty($article['author']))
                        <span>{{ $article['author'] }}@if (! empty($article['author_role'])) — {{ $article['author_role'] }}@endif</span>
                    @endif
                </div>
            </header>

            <figure class="news-article__figure" data-clip style="--r: 28px">
                <img
                    src="{{ asset($article['detail_image'] ?? $article['image']) }}"
                    alt="{{ $article['title'] }}"
                    fetchpriority="high"
                >
            </figure>

            <div class="news-article__body">
                @forelse ($article['sections'] ?? [] as $section)
                    <section aria-labelledby="article-section-{{ $loop->index }}">
                        <h2 id="article-section-{{ $loop->index }}">{{ $section['heading'] }}</h2>
                        @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </section>
                @empty
                    <p>{{ $article['excerpt'] }}</p>
                @endforelse
            </div>

            <footer class="news-article__footer">
                @if (! empty($article['author']))
                <div class="news-article__author">
                    <span class="news-article__avatar" aria-hidden="true">{{ mb_substr($article['author'], 0, 1) }}</span>
                    <span class="news-article__author-copy">
                        <strong>{{ $article['author'] }}</strong>
                        @if (! empty($article['author_role']))<span>{{ $article['author_role'] }}</span>@endif
                    </span>
                </div>
                @endif
                <div class="news-article__sharing" aria-label="Bagikan artikel">
                    <span>Bagikan</span>
                    <a
                        href="https://wa.me/?text={{ rawurlencode($article['title'] . ' ' . url()->current()) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Bagikan melalui WhatsApp"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 11.8a8.2 8.2 0 0 1-12.1 7.1L4 20l1.1-4A8.2 8.2 0 1 1 20.2 11.8Z"/><path d="M9 8.4c.2-.4.4-.4.7-.4h.4c.2 0 .3.1.4.4l.7 1.6c.1.2.1.3 0 .5l-.5.6c-.1.2-.2.3-.1.5.5.9 1.3 1.6 2.2 2 .2.1.4.1.5-.1l.7-.8c.2-.2.3-.2.5-.1l1.5.7c.2.1.3.2.3.4 0 .4-.2 1.2-.7 1.5-.5.4-1.1.6-1.8.4-.7-.2-1.6-.5-2.7-1.5-1.4-1.2-2.2-2.7-2.4-3.5-.2-.7 0-1.3.3-1.7Z"/></svg>
                    </a>
                    <a
                        href="mailto:?subject={{ rawurlencode($article['title']) }}&amp;body={{ rawurlencode(url()->current()) }}"
                        aria-label="Bagikan melalui email"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                    </a>
                </div>
            </footer>
            <a class="news-back-link" href="{{ route('news.index') }}"><span aria-hidden="true">←</span> Kembali ke berita</a>
        </article>
        @if ($related->isNotEmpty())
            <section class="news-related page-width" aria-labelledby="related-news-title">
                <h2 id="related-news-title" data-words>Berita <span>lainnya</span></h2>
                <div class="news-page__grid {{ $related->count() === 2 ? 'news-page__grid--pair' : '' }}" data-stagger="140">
                    @foreach ($related as $relatedArticle)
                        <x-news-card :article="$relatedArticle" :index="$loop->index" />
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
