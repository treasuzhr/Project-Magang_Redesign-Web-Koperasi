@props(['role' => 'status'])

<div {{ $attributes->class(['loan-info-box']) }} role="{{ $role }}" aria-live="polite">
    {{ $slot }}
</div>
