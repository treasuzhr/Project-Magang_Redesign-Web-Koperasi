<?php

namespace App\Filament\Resources\ShuPeriodes\Pages;

use App\Filament\Resources\ShuPeriodes\ShuPeriodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShuPeriodes extends ListRecords
{
    protected static string $resource = ShuPeriodeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat periode SHU')];
    }
}
