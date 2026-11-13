<?php

namespace App\Filament\Resources\Pinjamans\RelationManagers;

use App\AdminLoans;
use App\Models\Angsuran;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class AngsuransRelationManager extends RelationManager
{
    protected static string $relationship = 'angsurans';

    protected static ?string $title = 'Jadwal angsuran';

    public function isReadOnly(): bool
    {
        return auth()->user()?->role !== 'admin';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->role === 'admin';
    }

    #[On('loan-updated')]
    public function refreshSchedule(): void
    {
        $this->getOwnerRecord()->refresh();
    }

    public function table(Table $table): Table
    {
        $dateField = fn (): array => [DatePicker::make('tanggal_bayar')->label('Tanggal bayar')->required()->default(today())->displayFormat('d/m/Y')];

        return $table->columns([
            TextColumn::make('angsuran_ke')->label('Ke'), TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y')),
            TextColumn::make('pokok')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')), TextColumn::make('bunga')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            TextColumn::make('total')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            TextColumn::make('status')->badge()->formatStateUsing(fn (Angsuran $record): string => $record->status === 'lunas' ? 'Lunas' : ($record->jatuh_tempo->lt(today()) ? 'Tunggak' : 'Belum')),
            TextColumn::make('tanggal_bayar')->label('Tanggal bayar')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y'))->placeholder('-'),
        ])->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada jadwal angsuran')->defaultSort('angsuran_ke')->recordActions([
            Action::make('markPaid')->label('Tandai lunas')->schema($dateField)
                ->visible(fn (Angsuran $record): bool => $record->status !== 'lunas' && $this->getOwnerRecord()->status === 'berjalan')
                ->action(function (Angsuran $record, array $data): void {
                    app(AdminLoans::class)->markPaid($this->getOwnerRecord(), [$record->id], $data['tanggal_bayar']);
                })
                ->after(fn () => $this->dispatch('loan-updated'))->successNotificationTitle('Angsuran ditandai lunas.'),
        ])->toolbarActions([
            BulkAction::make('markPaid')->label('Tandai lunas')->schema($dateField)
                ->visible(fn (): bool => $this->getOwnerRecord()->status === 'berjalan')
                ->action(function (Collection $records, array $data): void {
                    app(AdminLoans::class)->markPaid($this->getOwnerRecord(), $records->modelKeys(), $data['tanggal_bayar']);
                })
                ->after(fn () => $this->dispatch('loan-updated'))->deselectRecordsAfterCompletion()->successNotificationTitle('Angsuran terpilih ditandai lunas.'),
        ]);
    }
}
