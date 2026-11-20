<?php

namespace App\Filament\Resources\Anggotas\Pages;

use App\AdminMemberAccounts;
use App\Filament\Resources\Anggotas\AnggotaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAnggota extends EditRecord
{
    protected static string $resource = AnggotaResource::class;

    public function getTitle(): string
    {
        return 'Ubah Anggota';
    }

    public function getBreadcrumb(): string
    {
        return 'Ubah';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan anggota berhasil disimpan.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['nip'] = $this->getRecord()->user->nip;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AdminMemberAccounts::class)->update($record, $data);
    }
}
