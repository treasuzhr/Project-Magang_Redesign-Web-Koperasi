<?php

namespace App\Filament\Resources\Pesanans;

use App\AdminOrders;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Filament\Resources\Pesanans\Pages\ViewPesanan;
use App\Models\Pesanan;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PesananResource extends AdminResource
{
    protected static ?string $model = Pesanan::class;

    protected static ?string $slug = 'pesanan';

    protected static ?string $modelLabel = 'Pesanan';

    protected static ?string $pluralModelLabel = 'Pesanan';

    protected static ?string $recordTitleAttribute = 'kode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function statusLabel(string $status): string
    {
        return $status === 'siap' ? 'Siap diambil' : ucfirst($status);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Pesanan::query()->where('status', 'baru')->count();
    }

    public static function workflowActions(): array
    {
        $actions = [];
        foreach (['diproses' => 'Proses', 'siap' => 'Siap diambil', 'selesai' => 'Selesaikan', 'batal' => 'Batalkan'] as $target => $label) {
            $actions[] = Action::make($target)->label($label)->requiresConfirmation()->color($target === 'batal' ? 'danger' : 'primary')
                ->visible(fn (Pesanan $record): bool => in_array($target, AdminOrders::TRANSITIONS[$record->status] ?? [], true))
                ->action(fn (Pesanan $record): Pesanan => app(AdminOrders::class)->transition($record, $target))->successNotificationTitle('Status pesanan diperbarui.');
        }

        return $actions;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('kode')->label('Kode')->searchable(), TextColumn::make('anggota.nama')->label('Anggota')->searchable(),
            TextColumn::make('total')->label('Total')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'baru' => 'warning', 'diproses' => 'info', 'siap' => 'primary', 'selesai' => 'success', 'batal' => 'danger', default => 'gray'
            })->formatStateUsing(fn (string $state): string => static::statusLabel($state)),
            TextColumn::make('tanggal')->label('Tanggal')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y H:i'))->sortable(),
        ])->filters([SelectFilter::make('status')->options(array_combine(Pesanan::STATUSES, array_map(static::statusLabel(...), Pesanan::STATUSES)))])
            ->recordActions([ViewAction::make()->label('Detail'), ...static::workflowActions()])->defaultPaginationPageOption(25)->emptyStateHeading(fn ($livewire): string => $livewire->activeTab === 'baru' ? 'Belum ada pesanan baru' : 'Tidak ada data pada antrean ini')->defaultSort('tanggal', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('kode')->label('Kode'), TextEntry::make('anggota.nama')->label('Anggota'),
            TextEntry::make('anggota.user.nip')->label('NIP/NRP'), TextEntry::make('tanggal')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y H:i')),
            TextEntry::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'baru' => 'warning', 'diproses' => 'info', 'siap' => 'primary', 'selesai' => 'success', 'batal' => 'danger', default => 'gray'
            })->formatStateUsing(fn (string $state): string => static::statusLabel($state)),
            TextEntry::make('total')->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')), TextEntry::make('catatan')->placeholder('-'),
            RepeatableEntry::make('items')->label('Item pesanan')->schema([
                TextEntry::make('nama')->label('Nama'), TextEntry::make('harga')->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextEntry::make('qty')->label('Jumlah'), TextEntry::make('subtotal')->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            ])->columns(4)->columnSpanFull(),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user', 'items']);
    }

    public static function getPages(): array
    {
        return ['index' => ListPesanans::route('/'), 'view' => ViewPesanan::route('/{record}')];
    }
}
