@props(['value'])

@php
    $cleanValue = preg_replace('/\D+/', '', (string) $value);

    if ($cleanValue === '') {
        echo e((string) $value);
        return;
    }

    $length = strlen($cleanValue);

    if ($length <= 8) {
        echo e($cleanValue);
        return;
    }

    $prefix = substr($cleanValue, 0, 4);
    $suffix = substr($cleanValue, -4);
    $masked = $prefix . str_repeat('•', max($length - 8, 0)) . $suffix;

    echo e($masked);
@endphp
