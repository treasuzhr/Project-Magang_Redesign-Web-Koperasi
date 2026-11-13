<?php

namespace App\Filament\Resources\Pinjamans\Pages;

use App\Filament\Resources\Pinjamans\PinjamanResource;
use App\Models\Pinjaman;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPinjamans extends ListRecords
{
    protected static string $resource = PinjamanResource::class;

    public function getTabs(): array
    {
        $counts = Pinjaman::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all();

        return ['diajukan' => Tab::make('Perlu diproses')->badge($counts['diajukan'] ?? 0)->badgeColor('warning')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'diajukan')->orderBy('tanggal_pengajuan')->orderBy('id')),
            'disetujui' => Tab::make('Siap dicairkan')->badge($counts['disetujui'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'disetujui')),
            'berjalan' => Tab::make('Berjalan')->badge($counts['berjalan'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'berjalan')),
            'lunas' => Tab::make('Lunas')->badge($counts['lunas'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'lunas')),
            'ditolak' => Tab::make('Ditolak')->badge($counts['ditolak'] ?? 0)->badgeColor('gray')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'ditolak')),
            'semua' => Tab::make('Semua')->badge(array_sum($counts)),
        ];
    }
}
