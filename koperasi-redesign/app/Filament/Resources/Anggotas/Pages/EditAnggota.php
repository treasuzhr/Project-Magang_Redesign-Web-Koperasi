<?php

namespace App\Filament\Resources\Anggotas\Pages;

use App\AdminMemberAccounts;
use App\Filament\Resources\Anggotas\AnggotaResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAnggota extends EditRecord
{
    protected static string $resource = AnggotaResource::class;

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
