<?php

namespace App\Filament\Widgets;

use App\AdminOverview;

class SavingsChart extends AdminChart
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Simpanan per bulan';

    protected ?string $description = 'Setor dan tarik per bulan pada tahun terpilih';

    protected bool $currencyAxis = true;

    protected ?string $emptyStateHeading = 'Belum ada data pada periode ini';

    protected function getFilters(): ?array
    {
        return $this->yearFilters(app(AdminOverview::class)->savingsYears());
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $year = $this->selectedYear();

        if ($year === null) {
            return [];
        }

        $series = app(AdminOverview::class)->monthlySavings($year);

        if (! $series['hasData']) {
            return [];
        }

        return ['labels' => $series['labels'], 'datasets' => [
            ['label' => 'Setor', 'data' => $series['setor'], 'backgroundColor' => '#4C7A10', 'borderRadius' => 4],
            ['label' => 'Tarik', 'data' => $series['tarik'], 'backgroundColor' => '#86B239', 'borderRadius' => 4],
        ]];
    }
}
