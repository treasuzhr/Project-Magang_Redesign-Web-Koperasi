<?php

namespace App\Filament\Widgets;

use App\AdminOverview;
use App\Filament\Resources\Anggotas\AnggotaResource;
use App\Filament\Resources\Pesanans\PesananResource;
use App\Filament\Resources\Pinjamans\PinjamanResource;
use App\Filament\Resources\SimpananTransaksis\SimpananTransaksiResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = ['default' => 1, '@md' => 2, '@xl' => 3, '@5xl' => 5];

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getStats(): array
    {
        $totals = app(AdminOverview::class)->totals();

        return [
            Stat::make('Anggota aktif', number_format($totals['active_members'], 0, ',', '.'))->icon(Heroicon::OutlinedUsers)->url(AnggotaResource::getUrl('index', ['filters' => ['status' => ['value' => 'aktif']]])),
            Stat::make('Total simpanan', 'Rp '.number_format($totals['savings'], 0, ',', '.'))->description('Setor dikurangi tarik, seluruh anggota')->icon(Heroicon::OutlinedWallet)->url(SimpananTransaksiResource::getUrl('index')),
            Stat::make('Pinjaman berjalan', number_format($totals['running_loans'], 0, ',', '.'))->icon(Heroicon::OutlinedBanknotes)->url(PinjamanResource::getUrl('index', ['tab' => 'berjalan'])),
            Stat::make('Pengajuan pinjaman', number_format($totals['pending_loans'], 0, ',', '.'))->description('Status diajukan')->icon(Heroicon::OutlinedDocumentText)->url(PinjamanResource::getUrl('index', ['tab' => 'diajukan'])),
            Stat::make('Pesanan baru', number_format($totals['new_orders'], 0, ',', '.'))->icon(Heroicon::OutlinedShoppingBag)->url(PesananResource::getUrl('index', ['tab' => 'baru'])),
        ];
    }
}
