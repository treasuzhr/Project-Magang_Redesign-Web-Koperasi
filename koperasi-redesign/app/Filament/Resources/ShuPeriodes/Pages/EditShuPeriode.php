<?php

namespace App\Filament\Resources\ShuPeriodes\Pages;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\ShuPeriodeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditShuPeriode extends EditRecord
{
    protected static string $resource = ShuPeriodeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(AdminShu::class)->save($data, $record);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all());
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, $data['parameter'] ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return ShuPeriodeResource::getUrl('index');
    }
}
