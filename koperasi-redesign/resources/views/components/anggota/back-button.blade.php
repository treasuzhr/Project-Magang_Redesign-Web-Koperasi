@props(['href', 'label' => 'Kembali'])

<a href="{{ $href }}" {{ $attributes->class(['member-back-button']) }}>
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 12H5m0 0 6-6m-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span>{{ $label }}</span>
</a>
