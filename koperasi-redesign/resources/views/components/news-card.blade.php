@props(['article', 'index' => 0])

@php
    $articleUrl = route('news.show', $article['slug'] ?? \Illuminate\Support\Str::slug($article['title']));
@endphp

<div class="news-card-reveal" data-reveal style="--d: {{ $index * 140 }}">
<article class="news-card" data-news-slug="{{ $article['slug'] ?? \Illuminate\Support\Str::slug($article['title']) }}">
    <a class="news-card__image ph" href="{{ $articleUrl }}" aria-label="Baca {{ $article['title'] }}">
        <img src="{{ asset($article['image']) }}" alt="{{ $article['title'] }}" loading="lazy">
    </a>
    <div class="news-card__body">
        <div class="news-card__meta"><time datetime="{{ $article['datetime'] }}">{{ $article['date'] }}</time><span>{{ $article['reading_minutes'] }} menit baca</span></div>
        <h3><a href="{{ $articleUrl }}"><span class="u">{{ $article['title'] }}</span></a></h3>
        <p>{{ $article['summary'] ?? $article['excerpt'] }}</p>
        <a class="news-card__more" href="{{ $articleUrl }}">Baca selengkapnya</a>
    </div>
</article>
</div>
