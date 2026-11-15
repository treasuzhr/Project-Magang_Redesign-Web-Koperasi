<?php

namespace App\Filament\Resources\Dokumens\Pages;

use App\AdminDocuments;
use App\Filament\Resources\Dokumens\DokumenResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditDokumen extends EditRecord
{
    protected static string $resource = DokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [DokumenResource::deletionAction()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(AdminDocuments::class)->save($data, $record);
        } catch (ValidationException $exception) {
            $messages = [];
            foreach ($exception->errors() as $field => $errors) {
                $messages['data.'.$field] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }
}
