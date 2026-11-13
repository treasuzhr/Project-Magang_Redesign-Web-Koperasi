<?php

namespace App\Filament\Widgets;

use App\AdminOverview;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totals = app(AdminOverview::class)->totals();

        return [
            Stat::make('Anggota aktif', number_format($totals['active_members'], 0, ',', '.'))->icon(Heroicon::OutlinedUsers),
            Stat::make('Total simpanan', 'Rp '.number_format($totals['savings'], 0, ',', '.'))->description('Setor dikurangi tarik, seluruh anggota')->icon(Heroicon::OutlinedWallet),
            Stat::make('Pinjaman berjalan', number_format($totals['running_loans'], 0, ',', '.'))->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Pengajuan pinjaman', number_format($totals['pending_loans'], 0, ',', '.'))->description('Status diajukan')->icon(Heroicon::OutlinedDocumentText),
            Stat::make('Pesanan baru', number_format($totals['new_orders'], 0, ',', '.'))->icon(Heroicon::OutlinedShoppingBag),
        ];
    }
}
