@extends('layouts.public')

@section('title', $article['title'] . ' | Berita Primkop Pullahta Hankam')
@section('description', $article['summary'] ?? $article['excerpt'])

@push('page-assets')
    @vite('resources/css/news-detail.css')
    <meta property="og:title" content="{{ $article['title'] }}">
    <meta property="og:description" content="{{ $article['summary'] ?? $article['excerpt'] }}">
    <meta property="og:image" content="{{ asset($article['detail_image'] ?? $article['image']) }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
@endpush

@section('content')
    <div class="news-reading-progress" data-news-progress aria-hidden="true"></div>
    <main class="news-article-page news-detail article-redesign" data-news-page>
        <article class="news-article page-width" data-reading-article>
            <nav class="news-article__breadcrumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('news.index') }}">Berita</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $article['title'] }}</span>
            </nav>

            <header class="news-article__header detail-enter">
                <p class="news-article__eyebrow">BERITA</p>
                <h1>{{ $article['title'] }}</h1>
                <div class="news-article__meta">
                    @if (! empty($article['author']))
                        <span class="news-article__avatar" aria-hidden="true">{{ mb_substr($article['author'], 0, 1) }}</span>
                        <span>{{ $article['author_role'] ?? $article['author'] }}</span>
                        <span aria-hidden="true">&middot;</span>
                    @endif
                    <time datetime="{{ $article['datetime'] }}">{{ $article['date'] }}</time>
                </div>
            </header>

            <figure class="news-article__figure detail-enter">
                <img
                    src="{{ asset($article['detail_image'] ?? $article['image']) }}"
                    alt="{{ $article['title'] }}"
                    fetchpriority="high" width="960" height="540"
                >
            </figure>

            <div class="news-article__body">
                @forelse ($article['sections'] ?? [] as $section)
                    <section @if (! empty($section['heading']))aria-labelledby="article-section-{{ $loop->index }}" @endif>
                        @if (! empty($section['heading']))<h2 id="article-section-{{ $loop->index }}">{{ $section['heading'] }}</h2>@endif
                        @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @if (! empty($section['image']))<x-news-inline-image :image="$section['image']" />@endif
                    </section>
                @empty
                    <p>{{ $article['excerpt'] }}</p>
                    <p class="news-article__pending">Isi berita sedang disiapkan.</p>
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
                    <span>Bagikan:</span>
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
                        target="_blank" rel="noopener noreferrer"
                        aria-label="Bagikan melalui email"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                    </a>
                    <a href="https://t.me/share/url?url={{ rawurlencode(url()->current()) }}&amp;text={{ rawurlencode($article['title']) }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan melalui Telegram">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m21 3-4 18-6-5-4 3 1-6-5-3L21 3Z"/><path d="m8 13 9-6-6 9"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode(url()->current()) }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan melalui Facebook">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M14 21v-8h3l1-4h-4V7c0-1 .5-2 2-2h2V2h-3c-4 0-5 2-5 5v2H7v4h3v8"/></svg>
                    </a>
                    <a href="https://x.com/intent/tweet?url={{ rawurlencode(url()->current()) }}&amp;text={{ rawurlencode($article['title']) }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan melalui X">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m4 3 16 18h-5L3 3h5ZM20 3 4 21"/></svg>
                    </a>
                    <button type="button" class="news-copy-link" data-copy-news data-url="{{ url()->current() }}" aria-label="Salin tautan berita" hidden>Salin tautan</button>
                    <span class="sr-only" data-copy-status role="status" aria-live="polite"></span>
                </div>
            </footer>
            <div class="news-article__navigation">
            <a class="button button--secondary news-back-link" href="{{ route('news.index') }}"><span aria-hidden="true">←</span> Kembali ke berita</a>
            </div>
        </article>
        @if ($related->isNotEmpty())
            <section class="news-related page-width" aria-labelledby="related-news-title">
                <h2 id="related-news-title">Berita <span>lainnya</span></h2>
                <div class="news-page__grid {{ $related->count() === 2 ? 'news-page__grid--pair' : '' }}" data-stagger="140">
                    @foreach ($related as $relatedArticle)
                        <x-news-card :article="$relatedArticle" :index="$loop->index" />
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
