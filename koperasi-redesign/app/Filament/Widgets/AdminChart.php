<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

abstract class AdminChart extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected bool $currencyAxis = false;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getOptions(): RawJs
    {
        $formatter = $this->currencyAxis ? "'Rp ' + new Intl.NumberFormat('id-ID').format(value)" : "new Intl.NumberFormat('id-ID').format(value)";

        return RawJs::make(<<<JS
        {
            locale: 'id-ID', animation: false, maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}, tooltip: {callbacks: {label: (context) => {
                const value = context.parsed.y ?? context.parsed;
                return context.dataset.label + ': ' + ($formatter);
            }}}},
            scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {precision: 0, callback: (value) => $formatter}}}
        }
        JS);
    }
}
