<?php

namespace App\Filament\Resources\Dokumens\Pages;

use App\Filament\Resources\Dokumens\DokumenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDokumens extends ListRecords
{
    protected static string $resource = DokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah dokumen')];
    }

    public function getTabs(): array
    {
        return ['terbit' => Tab::make('Terbit')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('published', true)), 'draf' => Tab::make('Draf')->modifyQueryUsing(fn (Builder $query): Builder => $query->where('published', false)), 'semua' => Tab::make('Semua')];
    }
}
