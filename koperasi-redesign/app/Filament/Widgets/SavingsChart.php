<?php

namespace App\Filament\Widgets;

use App\AdminOverview;

class SavingsChart extends AdminChart
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Simpanan per bulan';

    protected ?string $description = 'Setor dan tarik selama 12 bulan terakhir';

    protected bool $currencyAxis = true;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $series = app(AdminOverview::class)->monthlySavings();

        return ['labels' => $series['labels'], 'datasets' => [
            ['label' => 'Setor', 'data' => $series['setor'], 'backgroundColor' => '#4C7A10', 'borderRadius' => 4],
            ['label' => 'Tarik', 'data' => $series['tarik'], 'backgroundColor' => '#86B239', 'borderRadius' => 4],
        ]];
    }
}
