<?php

namespace App\Filament\Resources\ShuPeriodes\Pages;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\ShuPeriodeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateShuPeriode extends CreateRecord
{
    protected static string $resource = ShuPeriodeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AdminShu::class)->save($data);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all());
        }
    }

    protected function getRedirectUrl(): string
    {
        return ShuPeriodeResource::getUrl('index');
    }
}
