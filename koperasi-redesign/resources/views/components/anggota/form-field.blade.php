@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'help' => null,
    'error' => null,
    'inputmode' => null,
    'suffix' => null,
    'prefix' => null,
    'suffixControl' => null,
    'options' => [],
    'dynamicHelp' => false,
])

@php
    $helpId = $help !== null ? $id.'-help' : null;
    $errorId = $error !== null ? $id.'-error' : null;
    $describedBy = collect([$helpId, $errorId])->filter()->implode(' ');
@endphp

<div class="loan-field">
    <label class="loan-field__label" for="{{ $id }}">{{ $label }}</label>
    <div class="loan-field__input-wrap{{ $prefix !== null || isset($prefixContent) ? ' has-prefix' : '' }}{{ $suffix !== null || isset($suffixControl) ? ' has-suffix' : '' }}">
        @if ($prefix !== null)
            <span class="loan-field__affix loan-field__affix--prefix" aria-hidden="true">{{ $prefix }}</span>
        @elseif (isset($prefixContent))
            <span class="loan-field__affix loan-field__affix--prefix" aria-hidden="true">{{ $prefixContent }}</span>
        @endif
        @if ($type === 'select')
            <select
                id="{{ $id }}"
                name="{{ $name }}"
                required
                class="loan-field__control loan-field__select{{ $error !== null ? ' is-invalid' : '' }}"
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                @if ($error !== null) aria-invalid="true" @endif
                {{ $attributes }}
            >
                <option value="" disabled @selected((string) $value === '')>{{ $placeholder }}</option>
                @foreach ($options as $option)
                    <option value="{{ $option['value'] }}" @selected((string) $value === (string) $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @else
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ $value }}"
                placeholder="{{ $placeholder }}"
                class="loan-field__control{{ $error !== null ? ' is-invalid' : '' }}"
                @if ($inputmode !== null) inputmode="{{ $inputmode }}" @endif
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                @if ($error !== null) aria-invalid="true" @endif
                {{ $attributes }}
            >
        @endif
        @if ($suffix !== null)
            <span class="loan-field__affix loan-field__affix--suffix" aria-hidden="true">{{ $suffix }}</span>
        @endif
        @if (isset($suffixControl))
            <span class="loan-field__suffix-control">{{ $suffixControl }}</span>
        @endif
    </div>
    @if ($help !== null)
        <p class="loan-field__help" id="{{ $helpId }}" @if ($dynamicHelp) data-loan-total-help @endif>{{ $help }}</p>
    @endif
    @if ($error !== null)
        <p class="loan-field__error" id="{{ $errorId }}">{{ $error }}</p>
    @endif
</div>
