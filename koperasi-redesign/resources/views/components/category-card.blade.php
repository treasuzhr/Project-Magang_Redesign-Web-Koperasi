@props(['category'])

<article class="category-card">
    <div class="category-card__image">
        <img src="{{ asset($category['image']) }}" alt="" loading="lazy">
    </div>
    <div class="category-card__body">
        <div>
            <h3>{{ $category['name'] }}</h3>
            <p>{{ $category['description'] }}</p>
        </div>
        <span class="category-card__arrow" aria-hidden="true">›</span>
    </div>
</article>
