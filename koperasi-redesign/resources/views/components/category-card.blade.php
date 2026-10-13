@props(['category'])

<a class="category-card" href="{{ route('products.index', ['category' => $category['slug']]) }}">
    @if ($category['image'])
        <div class="category-card__image">
            <img src="{{ asset($category['image']) }}" alt="" loading="lazy">
        </div>
    @else
        <div class="category-card__image category-card__image--placeholder" aria-hidden="true"></div>
    @endif
    <div class="category-card__body">
        <div>
            <h3>{{ $category['name'] }}</h3>
            <p>{{ $category['description'] }}</p>
        </div>
        <span class="category-card__arrow" aria-hidden="true">›</span>
    </div>
</a>
