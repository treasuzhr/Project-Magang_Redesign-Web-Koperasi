<?php

namespace App\Filament\Resources\Kategoris\Pages;

use App\AdminCatalog;
use App\Filament\Resources\Kategoris\KategoriResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AdminCatalog::class)->saveCategory($data, $record);
    }

    protected function getHeaderActions(): array
    {
        return [KategoriResource::deletionAction()];
    }
}
