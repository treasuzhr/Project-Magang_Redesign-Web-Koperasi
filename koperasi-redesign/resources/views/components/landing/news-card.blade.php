@props(['article', 'index' => 0])

@php
    $articleUrl = isset($article['slug'])
        ? route('news.show', $article['slug'])
        : route('news.index') . '#news-list';
@endphp

<article class="landing-news-card" style="--news-card-delay: {{ $index * 120 }}ms">
    <a class="landing-news-card__image" href="{{ $articleUrl }}" aria-label="Baca {{ $article['title'] }}">
        <img src="{{ asset($article['image']) }}" alt="" loading="lazy">
    </a>
    <div class="landing-news-card__body">
        <div class="landing-news-card__meta">
            <time datetime="{{ $article['datetime'] ?? '' }}">{{ $article['date'] ?? '' }}</time>
        </div>
        <h3><a href="{{ $articleUrl }}">{{ $article['title'] }}</a></h3>
        <p>{{ $article['excerpt'] }}</p>
        <a class="landing-news-card__more" href="{{ $articleUrl }}">Baca selengkapnya <span aria-hidden="true">↗</span></a>
    </div>
</article>
