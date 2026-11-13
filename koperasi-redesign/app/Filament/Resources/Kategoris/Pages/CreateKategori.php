<?php

namespace App\Filament\Resources\Kategoris\Pages;

use App\AdminCatalog;
use App\Filament\Resources\Kategoris\KategoriResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminCatalog::class)->saveCategory($data);
    }

    protected function getRedirectUrl(): string
    {
        return KategoriResource::getUrl('index');
    }
}
