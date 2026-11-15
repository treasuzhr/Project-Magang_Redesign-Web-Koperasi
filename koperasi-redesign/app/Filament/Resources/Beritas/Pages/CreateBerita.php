<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\AdminNews;
use App\Filament\Resources\Beritas\BeritaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBerita extends CreateRecord
{
    protected static string $resource = BeritaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminNews::class)->save($data);
    }

    protected function getRedirectUrl(): string
    {
        return BeritaResource::getUrl('index');
    }
}
