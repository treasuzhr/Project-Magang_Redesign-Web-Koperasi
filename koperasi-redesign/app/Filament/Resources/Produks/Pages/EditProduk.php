<?php

namespace App\Filament\Resources\Produks\Pages;

use App\AdminCatalog;
use App\Filament\Resources\Produks\ProdukResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduk extends EditRecord
{
    protected static string $resource = ProdukResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AdminCatalog::class)->saveProduct($data, $record);
    }
}
