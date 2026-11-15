<?php

namespace App\Filament\Widgets;

use App\AdminOverview;

class OrdersChart extends AdminChart
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Pesanan per bulan';

    protected ?string $description = 'Jumlah pesanan per bulan pada tahun terpilih';

    protected ?string $emptyStateHeading = 'Belum ada data pada periode ini';

    protected function getFilters(): ?array
    {
        return $this->yearFilters(app(AdminOverview::class)->orderYears());
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $year = $this->selectedYear();

        if ($year === null) {
            return [];
        }

        $series = app(AdminOverview::class)->monthlyOrders($year);

        if (! $series['hasData']) {
            return [];
        }

        return ['labels' => $series['labels'], 'datasets' => [['label' => 'Pesanan', 'data' => $series['counts'], 'borderColor' => '#4C7A10', 'backgroundColor' => '#E9F0E0', 'fill' => true, 'tension' => 0.15, 'pointRadius' => 3]]];
    }
}
