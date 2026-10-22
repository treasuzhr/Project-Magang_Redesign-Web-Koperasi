@props(['categories'])

<section class="home-categories section" id="kategori" aria-labelledby="categories-title">
    <div class="page-width">
        <div class="home-section-intro home-section-intro--categories home-reveal">
            <div>
                <p class="section-eyebrow">Jelajahi pilihan</p>
                <h2 id="categories-title">Kebutuhan sehari-hari,<br><span>lebih mudah ditemukan.</span></h2>
            </div>
        </div>

        <div class="category-showcase" data-category-showcase role="region" aria-label="Pratinjau kategori produk">
            <div class="category-showcase__list" role="tablist" aria-label="Kategori produk">
                @foreach ($categories as $index => $category)
                    <button
                        class="category-showcase__item {{ $loop->first ? 'is-active' : '' }}"
                        id="category-tab-{{ $index }}"
                        type="button"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="category-preview-{{ $index }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        data-category-index="{{ $index }}"
                        data-category-name="{{ $category['name'] }}"
                        data-category-description="{{ $category['description'] }}"
                        data-category-url="{{ route('products.index', ['category' => $category['slug']]) }}"
                    >
                        <span class="category-showcase__item-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="category-showcase__item-copy">
                            <span class="category-showcase__item-name">{{ $category['name'] }}</span>
                            <span class="category-showcase__item-description">{{ $category['description'] }}</span>
                        </span>
                        <span class="category-showcase__item-arrow" aria-hidden="true">↗</span>
                        <span class="category-showcase__item-progress" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>

            <div class="category-showcase__preview">
                <span class="category-showcase__counter" aria-hidden="true">
                    <span data-category-current>01</span>
                    <span aria-hidden="true"> / {{ str_pad((string) count($categories), 2, '0', STR_PAD_LEFT) }}</span>
                </span>
                <div class="category-showcase__segments" aria-hidden="true">
                    @foreach ($categories as $index => $category)
                        <span data-category-segment="{{ $index }}"><i></i></span>
                    @endforeach
                </div>
                @foreach ($categories as $index => $category)
                    <div
                        class="category-showcase__panel {{ $loop->first ? 'is-active' : '' }}"
                        id="category-preview-{{ $index }}"
                        role="tabpanel"
                        aria-labelledby="category-tab-{{ $index }}"
                        data-category-panel="{{ $index }}"
                        @if (!$loop->first) hidden @endif
                    >
                        @if ($category['image'])
                            <img
                                src="{{ asset($category['image']) }}"
                                alt=""
                                @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                            >
                        @else
                            <div class="category-showcase__placeholder" aria-hidden="true">
                                <svg viewBox="0 0 96 96" fill="none"><path d="m18 70 20-24 14 16 10-12 16 20M28 26h40M28 38h24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/></svg>
                            </div>
                        @endif
                        <div class="category-showcase__caption">
                            <span class="category-showcase__caption-name">{{ $category['name'] }}</span>
                            <a href="{{ route('products.index', ['category' => $category['slug']]) }}">Lihat produk <span aria-hidden="true">↗</span></a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="category-showcase__mobile-details">
                <p class="category-showcase__mobile-description" aria-live="polite">{{ $categories[0]['description'] ?? '' }}</p>
                <a class="category-showcase__mobile-link" data-category-mobile-link href="{{ route('products.index', ['category' => $categories[0]['slug'] ?? '']) }}">Lihat produk <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </div>
</section>
