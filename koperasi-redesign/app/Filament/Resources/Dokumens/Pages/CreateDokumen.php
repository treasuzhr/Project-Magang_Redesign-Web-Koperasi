<?php

namespace App\Filament\Resources\Dokumens\Pages;

use App\AdminDocuments;
use App\Filament\Resources\Dokumens\DokumenResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateDokumen extends CreateRecord
{
    protected static string $resource = DokumenResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AdminDocuments::class)->save($data);
        } catch (ValidationException $exception) {
            $messages = [];
            foreach ($exception->errors() as $field => $errors) {
                $messages['data.'.$field] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }

    protected function getRedirectUrl(): string
    {
        return DokumenResource::getUrl('index');
    }
}
