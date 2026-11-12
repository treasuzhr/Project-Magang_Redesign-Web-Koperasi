@foreach ($products as $product)
                        <article class="sc-item" data-cart-item data-id="{{ $product['id'] }}" data-name="{{ $product['display_name'] }}" data-price="{{ $product['price'] }}" hidden>
                            <div class="sc-product">
                                <div class="sc-tile">
                                    @if ($product['image'])
                                        <img src="{{ asset($product['image']) }}" alt="" width="96" height="80" loading="lazy">
                                    @else
                                        <x-catalog-icon :name="$product['category'] ?? 'bag'" />
                                    @endif
                                </div>
                                <div class="sc-identity"><h3>{{ $product['display_name'] }}</h3>
                                    @if ($product['category_label'])<p>{{ $product['category_label'] }}</p>@endif
                                    <span>Rp {{ number_format($product['price'], 0, ',', '.') }} <span class="sc-unit">/ barang</span></span>
                                </div>
                            </div>
                            <div class="sc-stepper" role="group" aria-label="Jumlah {{ $product['display_name'] }}">
                                <button type="button" data-cart-change="-1" aria-label="Kurangi {{ $product['display_name'] }}">&minus;</button>
                                <output data-cart-quantity aria-label="Jumlah {{ $product['display_name'] }}">0</output>
                                <button type="button" data-cart-change="1" aria-label="Tambah {{ $product['display_name'] }}">+</button>
                            </div>
                            <strong class="sc-subtotal" data-cart-subtotal></strong>
                            <button class="sc-remove" type="button" data-cart-remove aria-label="Hapus {{ $product['display_name'] }}" title="Hapus produk">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg>
                            </button>
                        </article>

@endforeach
