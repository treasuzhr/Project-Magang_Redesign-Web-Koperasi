<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\AdminNews;
use App\Filament\Resources\Beritas\BeritaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBerita extends EditRecord
{
    protected static string $resource = BeritaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Hapus')];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['is_published'] = $this->getRecord()->published_at !== null;
        $data['isi'] = app(AdminNews::class)->editorContent($this->getRecord());

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(AdminNews::class)->save($data, $record);
    }
}
