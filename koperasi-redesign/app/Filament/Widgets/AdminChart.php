<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

abstract class AdminChart extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    protected bool $currencyAxis = false;

    public function mount(): void
    {
        $filters = $this->getFilters();

        if (($this->filter === null) && ($filters !== null)) {
            $this->filter = (string) array_key_first($filters);
        }

        parent::mount();
    }

    /**
     * @param  array<int, int>  $years
     * @return array<string, string>|null
     */
    protected function yearFilters(array $years): ?array
    {
        if ($years === []) {
            return null;
        }

        $years = array_map('strval', $years);

        return array_combine($years, $years);
    }

    protected function selectedYear(): ?int
    {
        if (($this->filter === null) || (! ctype_digit($this->filter))) {
            return null;
        }

        $year = (int) $this->filter;

        return array_key_exists($year, $this->getFilters() ?? []) ? $year : null;
    }

    /**
     * @return array<string, int>
     */
    protected function getAnimationOptions(): array
    {
        return ['duration' => 0];
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getOptions(): RawJs
    {
        $formatter = $this->currencyAxis ? "'Rp ' + new Intl.NumberFormat('id-ID').format(value)" : "new Intl.NumberFormat('id-ID').format(value)";
        $animationDuration = $this->getAnimationOptions()['duration'];

        return RawJs::make(<<<JS
        {
            locale: 'id-ID', animation: {duration: {$animationDuration}}, maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}, tooltip: {callbacks: {label: (context) => {
                const value = context.parsed.y ?? context.parsed;
                return context.dataset.label + ': ' + ($formatter);
            }}}},
            scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {precision: 0, callback: (value) => $formatter}}}
        }
        JS);
    }
}
