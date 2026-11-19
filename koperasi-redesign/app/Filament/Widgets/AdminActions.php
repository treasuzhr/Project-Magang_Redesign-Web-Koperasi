<?php

namespace App\Filament\Widgets;

use App\AdminOverview;
use App\Filament\Resources\Pesanans\PesananResource;
use App\Filament\Resources\Pinjamans\PinjamanResource;
use Filament\Widgets\Widget;

class AdminActions extends Widget
{
    private const TABS = ['loans', 'orders', 'installments'];

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-actions';

    public string $activeTab = 'loans';

    /** @var array{loans: int, orders: int, installments: int} */
    public array $counts = ['loans' => 0, 'orders' => 0, 'installments' => 0];

    /** @var array<int, array{primary: string, secondary: string, amount: int, url: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->counts = app(AdminOverview::class)->actionCounts();
        $this->activeTab = collect(self::TABS)->first(fn (string $tab): bool => $this->counts[$tab] > 0) ?? self::TABS[0];
        $this->loadItems();
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, self::TABS, true), 404);

        $this->activeTab = $tab;
        $this->loadItems();
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'counts' => $this->counts,
            'items' => $this->items,
            'tabs' => [
                'loans' => [
                    'label' => 'Pengajuan pinjaman',
                    'empty' => 'Tidak ada pengajuan yang perlu diproses',
                    'all_url' => PinjamanResource::getUrl('index', ['tab' => 'diajukan']),
                ],
                'orders' => [
                    'label' => 'Pesanan baru',
                    'empty' => 'Tidak ada pesanan baru',
                    'all_url' => PesananResource::getUrl('index', ['tab' => 'baru']),
                ],
                'installments' => [
                    'label' => 'Angsuran lewat jatuh tempo',
                    'empty' => 'Tidak ada angsuran yang lewat jatuh tempo',
                    'all_url' => PinjamanResource::getUrl('index', ['tab' => 'berjalan']),
                ],
            ],
        ];
    }

    private function loadItems(): void
    {
        $this->items = app(AdminOverview::class)->actionItems($this->activeTab);
    }
}
