<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\Pesanan;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPesanans extends ListRecords
{
    protected static string $resource = PesananResource::class;

    public function getTabs(): array
    {
        $counts = Pesanan::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all();

        return ['baru' => Tab::make('Baru')->badge($counts['baru'] ?? 0)->badgeColor('warning')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'baru')->orderBy('tanggal')->orderBy('id')),
            'diproses' => Tab::make('Diproses')->badge($counts['diproses'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'diproses')),
            'siap' => Tab::make('Siap diambil')->badge($counts['siap'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'siap')),
            'selesai' => Tab::make('Selesai')->badge($counts['selesai'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'selesai')),
            'batal' => Tab::make('Batal')->badge($counts['batal'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'batal')),
            'semua' => Tab::make('Semua')->badge(array_sum($counts)),
        ];
    }
}
