<?php

namespace App\Filament\Resources\Produks\Pages;

use App\AdminCatalog;
use App\Filament\Resources\Produks\ProdukResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduk extends CreateRecord
{
    protected static string $resource = ProdukResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminCatalog::class)->saveProduct($data);
    }

    protected function getRedirectUrl(): string
    {
        return ProdukResource::getUrl('index');
    }
}
