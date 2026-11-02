@props(['name' => 'bag'])
@php
    $path = match ($name) {
        'cart' => 'M2 3h3l3 12h11l3-9H6M9 20h.01M18 20h.01M8 15l-1 3h13',
        'search' => 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'list' => 'M9 5h12M9 12h12M9 19h12M3 5h1M3 12h1M3 19h1',
        'filter' => 'M3 6h18M6 12h12M9 18h6M7 3v6M16 9v6M11 15v6',
        'atk-perlengkapan-kantor' => 'M4 20l1-5L16 4a2 2 0 0 1 4 4L9 19zM14 6l4 4M4 20h16',
        'kebersihan-rumah' => 'M8 21V10l4-3 4 3v11zM10 7V3h4v4M10 3h7M8 14h8',
        'perawatan-diri' => 'M8 21V9h8v12zM10 9V5h4v4M10 5V2h8M8 15h8',
        'kesehatan-obat-obatan' => 'M6 3h12v18H6zM9 3V1h6v2M12 9v8M8 13h8',
        'sembako' => 'M6 21V9l2-5h8l2 5v12zM8 4V2h8v2M6 10h12M9 15h6M10 18h4',
        'minuman-susu' => 'M7 21V8l3-5h7v18zM7 8h10M10 3l3 5M11 13h3M11 16h3',
        'makanan-camilan' => 'M5 3h14l-1 7 1 11H5l1-11zM5 6h14M5 18h14M9 11h6v4H9z',
        'pakaian-perlengkapan-rumah' => 'M8 3l4 2 4-2 6 5-4 4-2-2v11H8V10l-2 2-4-4z',
        'rokok' => 'M3 13h18v5H3zM15 13v5M7 9c3-3-3-3 0-6M12 9c3-3-3-3 0-6',
        default => 'M4 7h16l-1 14H5zM8 8V5a4 4 0 0 1 8 0v3',
    };
@endphp
<svg {{ $attributes->merge(['width' => '24', 'height' => '24', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.5', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}><path d="{{ $path }}" /></svg>
