@props(['eyebrow', 'title', 'id'])

<div class="section-heading">
    <div>
        <p class="section-eyebrow">{{ $eyebrow }}</p>
        <h2 id="{{ $id }}">{{ $title }}</h2>
    </div>
    {{ $slot }}
</div>
