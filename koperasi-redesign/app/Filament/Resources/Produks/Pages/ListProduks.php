<?php

namespace App\Filament\Resources\Produks\Pages;

use App\Filament\Resources\Produks\ProdukResource;
use App\Models\Produk;
use App\ProductCsvImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    public ?array $importResult = null;

    public function getTabs(): array
    {
        $counts = Produk::query()->selectRaw('aktif, COUNT(*) AS total')->groupBy('aktif')->pluck('total', 'aktif');

        return [
            'aktif' => Tab::make('Aktif')->badge($counts[1] ?? 0)->modifyQueryUsing(fn (Builder $query) => $query->where('aktif', true)),
            'nonaktif' => Tab::make('Nonaktif')->badge($counts[0] ?? 0)->modifyQueryUsing(fn (Builder $query) => $query->where('aktif', false)),
            'semua' => Tab::make('Semua')->badge($counts->sum()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah produk'),
            Action::make('importProducts')->label('Impor produk')->schema([
                FileUpload::make('csv')->label('CSV produk')->required()->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])->maxSize(2048)->disk('local')->directory('admin-imports')->visibility('private')->preventFilePathTampering(),
            ])->action(function (array $data): void {
                $path = $data['csv'];
                if (! is_string($path) || ! str_starts_with($path, 'admin-imports/') || str_contains($path, '..')) {
                    throw ValidationException::withMessages(['csv' => 'File CSV tidak valid.']);
                }
                try {
                    $this->importResult = app(ProductCsvImporter::class)->import(Storage::disk('local')->path($path));
                } catch (ValidationException $exception) {
                    $messages = [];
                    $statePath = $this->getMountedActionSchema()->getStatePath();
                    foreach ($exception->errors() as $field => $errors) {
                        $messages[$statePath.'.'.$field] = $errors;
                    }
                    throw ValidationException::withMessages($messages);
                } finally {
                    Storage::disk('local')->delete($path);
                }
                $result = $this->importResult;
                Notification::make()->title('Impor produk selesai')->body("Dibuat: {$result['created']}. Diperbarui: {$result['updated']}. Dilewati: {$result['skipped']}. Rincian tersedia pada Hasil impor terakhir.")->success()->persistent()->send();
            }),
            Action::make('importResult')->label('Hasil impor terakhir')->visible(fn (): bool => $this->importResult !== null)->schema([
                Textarea::make('report')->label('Ringkasan dan alasan per baris')->default(fn (): string => $this->reportText())->readOnly()->rows(12),
            ])->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
            Action::make('template')->label('Unduh templat CSV')->url(fn (): string => route('admin.produk.template')),
        ];
    }

    private function reportText(): string
    {
        if ($this->importResult === null) {
            return '';
        }
        $result = $this->importResult;
        $lines = ["Dibuat: {$result['created']}", "Diperbarui: {$result['updated']}", "Dilewati: {$result['skipped']}"];
        foreach ($result['errors'] as $error) {
            $lines[] = "Baris {$error['row']}: {$error['reason']}";
        }

        return implode("\n", $lines);
    }
}
