<?php

namespace App\Filament\Widgets;

use App\AdminOverview;
use Filament\Support\RawJs;

class LoanStatusChart extends AdminChart
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Pinjaman menurut status';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $series = app(AdminOverview::class)->loanStatuses();

        return ['labels' => $series['labels'], 'datasets' => [['label' => 'Pinjaman', 'data' => $series['counts'], 'backgroundColor' => ['#86B239', '#4C7A10', '#0E3B2B', '#A5B49E', '#D9E3D2'], 'borderWidth' => 2, 'borderColor' => '#FFFFFF']]];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make("{locale: 'id-ID', animation: false, maintainAspectRatio: false, cutout: '68%', plugins: {legend: {position: 'bottom'}, tooltip: {callbacks: {label: (context) => context.label + ': ' + new Intl.NumberFormat('id-ID').format(context.parsed) + ' pinjaman'}}}}");
    }
}
