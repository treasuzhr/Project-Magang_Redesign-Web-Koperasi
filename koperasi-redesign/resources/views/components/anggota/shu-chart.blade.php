@props(['data', 'latestYear'])

@php
    $maxValue = max(array_column($data, 'amount'));
    $chartLabel = 'Grafik SHU: '.collect($data)
        ->map(fn (array $item): string => $item['year'].' Rp '.number_format($item['amount'], 0, ',', '.'))
        ->implode(', ');
@endphp

<div class="shu-chart" role="img" aria-label="{{ $chartLabel }}" data-shu-chart>
    @foreach ($data as $item)
        @php
            $formattedAmount = 'Rp '.number_format($item['amount'], 0, ',', '.');
            $barHeight = ($item['amount'] / $maxValue) * 100;
        @endphp
        <button
            class="shu-chart__bar-button"
            type="button"
            aria-label="SHU tahun {{ $item['year'] }}: {{ $formattedAmount }}"
            aria-pressed="false"
            data-chart-bar
            style="--bar-height: {{ $barHeight }}%; --bar-delay: {{ ($loop->index * 70) }}ms"
        >
            <span class="shu-chart__bar-track" aria-hidden="true">
                <span class="shu-chart__tooltip" aria-hidden="true">{{ $item['year'] }}: {{ $formattedAmount }}</span>
                <span class="shu-chart__bar{{ $item['year'] === $latestYear ? ' is-current' : '' }}"></span>
            </span>
            <span class="shu-chart__year" aria-hidden="true">{{ $item['year'] }}</span>
        </button>
    @endforeach
</div>
