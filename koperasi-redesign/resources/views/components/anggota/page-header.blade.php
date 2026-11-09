@props(['eyebrow', 'title', 'accent' => null, 'suffix' => null, 'accentSuffix' => null, 'subtitle' => null])

<header class="member-page-header member-reveal" data-reveal>
    <div>
        <p class="member-page-header__eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $title }}@if ($accent !== null) <span>{{ $accent }}</span>@endif @if ($suffix !== null) {{ $suffix }}@endif @if ($accentSuffix !== null) <span>{{ $accentSuffix }}</span>@endif</h1>
        @if ($subtitle !== null)
            <p class="member-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="member-page-header__actions">{{ $actions }}</div>
    @endif
</header>
