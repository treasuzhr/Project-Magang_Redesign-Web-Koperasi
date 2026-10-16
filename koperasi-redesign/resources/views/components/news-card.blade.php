@props(['article'])

@php
    $articleUrl = isset($article['slug'])
        ? route('news.show', $article['slug'])
        : route('news.index') . '#news-list';
@endphp

<article class="news-card">
    <a class="news-card__image" href="{{ $articleUrl }}" aria-label="Lihat berita {{ $article['title'] }}">
        <img src="{{ asset($article['image']) }}" alt="{{ $article['title'] }}" loading="lazy">
    </a>
    <div class="news-card__body">
        <time datetime="{{ $article['datetime'] }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M8 14h2m4 0h2M8 17h2m4 0h2"/></svg>
            {{ $article['date'] }}
        </time>
        <h3><a href="{{ $articleUrl }}">{{ $article['title'] }}</a></h3>
        <p>{{ $article['excerpt'] }}</p>
        <a class="news-card__more" href="{{ $articleUrl }}">Baca selengkapnya <span aria-hidden="true">›</span></a>
    </div>
</article>
