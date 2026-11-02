@props(['image'])

@php
    $source = $image['src'] ?? null;
    $publicRoot = realpath(public_path());
    $resolved = is_string($source) && ! preg_match('/^(?:[a-z]+:|[\\\\\/])/i', $source)
        ? realpath(public_path($source))
        : false;
    $available = $resolved !== false
        && $publicRoot !== false
        && str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($resolved)
        && in_array(strtolower(pathinfo($resolved, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'], true)
        && @getimagesize($resolved) !== false;
    $portrait = ! empty($image['portrait']);
@endphp

@if ($available)
    <figure class="news-inline-image {{ $portrait ? 'news-inline-image--portrait' : '' }}" data-news-inline-image>
        <img src="{{ asset($source) }}" alt="{{ $image['alt'] ?? '' }}" width="{{ $portrait ? 480 : 760 }}" height="{{ $portrait ? 560 : 428 }}" loading="lazy" decoding="async">
    </figure>
@endif
