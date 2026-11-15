<?php

namespace App\Filament\Widgets;

use App\AdminOverview;

class OrdersChart extends AdminChart
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Pesanan per bulan';

    protected ?string $description = 'Jumlah pesanan berdasarkan tanggal pesanan, 12 bulan terakhir';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = app(AdminOverview::class)->monthlyOrders();

        return ['labels' => $series['labels'], 'datasets' => [['label' => 'Pesanan', 'data' => $series['counts'], 'borderColor' => '#4C7A10', 'backgroundColor' => '#E9F0E0', 'fill' => true, 'tension' => 0.15, 'pointRadius' => 3]]];
    }
}
