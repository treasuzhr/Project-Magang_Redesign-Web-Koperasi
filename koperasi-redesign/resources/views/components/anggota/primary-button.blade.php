@props(['type' => 'button'])

<button type="{{ $type }}" {{ $attributes->class(['loan-primary-button']) }}>
    {{ $slot }}
</button>
