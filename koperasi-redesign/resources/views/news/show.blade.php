@extends('layouts.public')

@section('title', $article['title'] . ' | Berita Primkop Pullahta Hankam')
@section('description', $article['excerpt'])

@section('content')
    <main class="news-article-page">
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
                <h1>{{ $article['title'] }}</h1>
                <div class="news-article__meta">
                    <time datetime="{{ $article['datetime'] }}">{{ $article['date'] }}</time>
                    <span class="news-article__meta-divider" aria-hidden="true"></span>
                    <span>{{ $article['author'] }} <span aria-hidden="true">—</span> {{ $article['author_role'] }}</span>
                </div>
            </header>

            <figure class="news-article__figure">
                <img
                    src="{{ asset($article['detail_image']) }}"
                    alt="Layanan simpan pinjam dan unit usaha Primkop Pullahta Hankam"
                    fetchpriority="high"
                >
            </figure>

            <div class="news-article__body">
                @foreach ($article['sections'] as $section)
                    <section aria-labelledby="article-section-{{ $loop->index }}">
                        <h2 id="article-section-{{ $loop->index }}">{{ $section['heading'] }}</h2>
                        @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </section>
                @endforeach
            </div>

            <footer class="news-article__footer">
                <div class="news-article__author">
                    <span class="news-article__avatar" aria-hidden="true">A</span>
                    <span class="news-article__author-copy">
                        <strong>{{ $article['author'] }}</strong>
                        <span>{{ $article['author_role'] }}</span>
                    </span>
                </div>
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
        </article>
    </main>
@endsection
