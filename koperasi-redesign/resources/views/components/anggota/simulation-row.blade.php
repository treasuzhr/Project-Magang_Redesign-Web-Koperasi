@props(['label', 'value' => '-', 'emphasis' => false])

<div class="loan-simulation-row{{ $emphasis ? ' loan-simulation-row--emphasis' : '' }}">
    <span>{{ $label }}</span>
    <strong>{{ $value }}</strong>
</div>
