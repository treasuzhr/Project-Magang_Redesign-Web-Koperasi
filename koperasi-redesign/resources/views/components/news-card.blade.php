@props(['article'])

<article class="news-card">
    <a class="news-card__image" href="#berita" aria-label="Baca {{ $article['title'] }}">
        <img src="{{ asset($article['image']) }}" alt="" loading="lazy">
    </a>
    <div class="news-card__body">
        <time datetime="{{ $article['datetime'] }}">{{ $article['date'] }}</time>
        <h3><a href="#berita">{{ $article['title'] }}</a></h3>
        <p>{{ $article['excerpt'] }}</p>
        <a class="news-card__more" href="#berita">Baca selengkapnya <span aria-hidden="true">›</span></a>
    </div>
</article>
