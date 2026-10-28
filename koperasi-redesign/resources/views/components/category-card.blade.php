@props(['category'])

<a class="category-card {{ $category['image'] ? '' : 'category-card--placeholder' }}" href="{{ route('products.index', ['category' => $category['slug']]) }}">
    @if ($category['image'])
        <div class="category-card__image">
            <img src="{{ asset($category['image']) }}" alt="" loading="lazy">
        </div>
    @else
        <div class="category-card__image category-card__image--placeholder" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none"><path d="M9 35 19 23l7 8 5-6 8 10M14 13h20M14 19h10" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
        </div>
    @endif
    <span class="category-card__number" aria-hidden="true"></span>
    <div class="category-card__body">
        <div>
            <h3>{{ $category['name'] }}</h3>
            <p>{{ $category['description'] }}</p>
        </div>

    </div>
</a>
