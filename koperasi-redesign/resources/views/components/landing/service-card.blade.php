@props(['number', 'theme', 'title', 'description', 'button', 'href'])

<article class="service-card service-card--{{ $theme }}" style="--k: {{ $number }}">
    <div class="service-card__copy">
        <span class="service-card__number" data-service-number><span>0{{ (int) $number + 1 }}</span> / 04</span>
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
        <a class="service-card__button" href="{{ $href }}">{{ $button }}</a>
    </div>
    <div class="service-card__visual">
        {{ $slot }}
    </div>
</article>
