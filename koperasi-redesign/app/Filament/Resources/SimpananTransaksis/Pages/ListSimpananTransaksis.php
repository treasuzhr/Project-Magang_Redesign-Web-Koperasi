<?php

namespace App\Filament\Resources\SimpananTransaksis\Pages;

use App\AdminSavings;
use App\Filament\Resources\SimpananTransaksis\SimpananTransaksiResource;
use App\Models\SimpananTransaksi;
use App\SavingsCsvImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListSimpananTransaksis extends ListRecords
{
    protected static string $resource = SimpananTransaksiResource::class;

    public ?array $importResult = null;

    public function getTabs(): array
    {
        $counts = SimpananTransaksi::query()->selectRaw('tipe, COUNT(*) AS total')->groupBy('tipe')->pluck('total', 'tipe');

        return ['semua' => Tab::make('Semua')->badge($counts->sum()), 'setor' => Tab::make('Setor')->badge($counts['setor'] ?? 0)->modifyQueryUsing(fn (Builder $query): Builder => $query->where('tipe', 'setor')), 'tarik' => Tab::make('Tarik')->badge($counts['tarik'] ?? 0)->modifyQueryUsing(fn (Builder $query): Builder => $query->where('tipe', 'tarik'))];
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('record')->label('Catat transaksi')->schema(SimpananTransaksiResource::transactionFields())
            ->action(fn (array $data): SimpananTransaksi => SimpananTransaksiResource::validateAction($this, fn (): SimpananTransaksi => app(AdminSavings::class)->record($data)))->successNotificationTitle('Transaksi dicatat'),
            Action::make('importSavings')->label('Impor setoran')->schema([FileUpload::make('csv')->required()->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])->maxSize(2048)->disk('local')->directory('admin-imports')->visibility('private')->preventFilePathTampering()])
                ->action(function (array $data): void {
                    $path = $data['csv'];
                    if (! is_string($path) || ! str_starts_with($path, 'admin-imports/') || str_contains($path, '..')) {
                        throw ValidationException::withMessages(['csv' => 'File CSV tidak valid.']);
                    }
                    try {
                        $this->importResult = app(SavingsCsvImporter::class)->import(Storage::disk('local')->path($path));
                    } catch (ValidationException $exception) {
                        $messages = [];
                        foreach ($exception->errors() as $field => $errors) {
                            $messages[$this->getMountedActionSchema()->getStatePath().'.'.$field] = $errors;
                        }throw ValidationException::withMessages($messages);
                    } finally {
                        Storage::disk('local')->delete($path);
                    }
                    Notification::make()->title('Impor setoran selesai')->body('Dibuat: '.$this->importResult['created'].'. Dilewati: '.$this->importResult['skipped'].'. Lihat Hasil impor terakhir untuk alasan per baris.')->success()->persistent()->send();
                }),
            Action::make('importResult')->label('Hasil impor terakhir')->visible(fn (): bool => $this->importResult !== null)->schema([Textarea::make('report')->label('Ringkasan dan alasan per baris')->readOnly()->rows(12)->default(fn (): string => $this->reportText())])->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
            Action::make('template')->label('Unduh templat CSV')->url(fn (): string => route('admin.simpanan.template'))];
    }

    private function reportText(): string
    {
        if ($this->importResult === null) {
            return '';
        }

        return 'Dibuat: '.$this->importResult['created'].'. Dilewati: '.$this->importResult['skipped'].PHP_EOL.collect($this->importResult['errors'])->map(fn (array $error): string => 'Baris '.$error['row'].': '.$error['reason'])->implode(PHP_EOL);
    }
}
