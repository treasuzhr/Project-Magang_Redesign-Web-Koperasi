<?php

namespace App\Filament\Resources\Anggotas\Pages;

use App\AdminMemberAccounts;
use App\Filament\Resources\Anggotas\AnggotaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAnggota extends CreateRecord
{
    protected static string $resource = AnggotaResource::class;

    public function getTitle(): string
    {
        return 'Tambah Anggota';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Simpan & tambah lagi');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Anggota berhasil ditambahkan.';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminMemberAccounts::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return AnggotaResource::getUrl('index');
    }
}
