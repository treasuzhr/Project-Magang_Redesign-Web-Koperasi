@props(['product', 'index' => 0])
<article class="pc-card" data-product-card data-product-id="{{ $product['id'] }}" data-product-name="{{ $product['display_name'] }}" data-product-price="{{ $product['price'] }}" @if ($index < 8) data-card-reveal style="--card-delay: {{ $index * 40 }}ms" @endif>
    @php
        $tileAngle = 90 + (hexdec(substr(hash('sha256', (string) $product['id']), 0, 4)) % 181);
        $initials = collect(preg_split('/\s+/u', trim($product['display_name'])))->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
    @endphp
    <div class="pc-card__tile" style="--tile-angle: {{ $tileAngle }}deg">
        @if (!empty($product['image']))
            <img src="{{ asset($product['image']) }}" alt="{{ $product['display_name'] }}" loading="lazy" width="400" height="300">
        @else
            <span class="pc-card__watermark" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
            <x-catalog-icon :name="$product['category'] ?? 'bag'" class="pc-card__illustration" />
        @endif
        @if ($product['category_label'])<span class="pc-card__badge">{{ $product['category_label'] }}</span>@endif
    </div>
    <div class="pc-card__body">
        <div class="pc-card__identity">
            <h3 title="{{ $product['display_name'] }}">{{ $product['display_name'] }}</h3>
            @if ($product['category_label'])<p class="pc-card__category">{{ $product['category_label'] }}</p>@endif
        </div>
        <div class="pc-card__purchase">
            <p class="pc-card__price">Rp {{ number_format($product['price'], 0, ',', '.') }}</p>
            {{-- Pilihan disimpan sebagai draf di browser; pesanan belum dikirim ke server. --}}
            <div class="pc-card__actions">
                <button type="button" class="pc-add" data-add-product aria-label="Tambah ke keranjang: {{ $product['display_name'] }}" title="Tambah ke keranjang" disabled><x-catalog-icon name="cart" /><span class="pc-add__list-label">Tambah</span></button>
                <div class="pc-quantity" data-product-quantity hidden>
                    <button type="button" data-change-quantity="-1" aria-label="Kurangi {{ $product['display_name'] }}">&minus;</button>
                    <output data-quantity-value aria-label="Jumlah {{ $product['display_name'] }}">0</output>
                    <button type="button" data-change-quantity="1" aria-label="Tambah {{ $product['display_name'] }}">+</button>
                </div>
            </div>
        </div>
    </div>
</article>
