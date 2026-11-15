<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\Filament\Resources\Beritas\BeritaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBeritas extends ListRecords
{
    protected static string $resource = BeritaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah berita')];
    }

    public function getTabs(): array
    {
        return ['terbit' => Tab::make('Terbit')->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('published_at')), 'draf' => Tab::make('Draf')->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('published_at')), 'semua' => Tab::make('Semua')];
    }
}
