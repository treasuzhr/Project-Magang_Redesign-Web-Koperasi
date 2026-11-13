<?php

namespace App\Filament\Resources\Anggotas\Pages;

use App\AdminMemberAccounts;
use App\Filament\Resources\Anggotas\AnggotaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAnggota extends CreateRecord
{
    protected static string $resource = AnggotaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminMemberAccounts::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return AnggotaResource::getUrl('index');
    }
}
