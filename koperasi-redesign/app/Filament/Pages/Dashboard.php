<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminActions;
use App\Filament\Widgets\AdminStats;
use App\Filament\Widgets\LoanStatusChart;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\SavingsChart;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $title = 'Dashboard Admin';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }

    public function getWidgets(): array
    {
        return [AdminStats::class, AdminActions::class, SavingsChart::class, OrdersChart::class, LoanStatusChart::class];
    }
}
