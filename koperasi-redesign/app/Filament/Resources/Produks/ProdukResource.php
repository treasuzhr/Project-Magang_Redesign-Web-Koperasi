<?php

namespace App\Filament\Resources\Produks;

use App\AdminCatalog;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Produks\Pages\CreateProduk;
use App\Filament\Resources\Produks\Pages\EditProduk;
use App\Filament\Resources\Produks\Pages\ListProduks;
use App\Models\Kategori;
use App\Models\Produk;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProdukResource extends AdminResource
{
    protected static ?string $model = Produk::class;

    protected static ?string $slug = 'produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->label('Nama')->required()->maxLength(255)->unique(ignoreRecord: true),
            Select::make('kategori_id')->label('Kategori')->relationship('kategori', 'nama')->searchable()->preload()->nullable(),
            TextInput::make('harga')->label('Harga (Rp)')->numeric()->step(1)->minValue(0)->maxValue(PHP_INT_MAX)->rules(['integer'])->required(),
            Toggle::make('aktif')->label('Aktif')->default(true)->required(),
            FileUpload::make('foto')->label('Foto')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(2048)->disk('public')->directory('produk')->visibility('public')->preventFilePathTampering(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama')->label('Nama')->searchable()->sortable(), SelectColumn::make('kategori_id')->label('Kategori')->options(fn (): array => Kategori::query()->orderBy('nama')->pluck('nama', 'id')->all())->selectablePlaceholder(false)->rules(['required', 'integer', 'exists:kategoris,id'])->disabled(fn (): bool => ! static::canViewAny())->updateStateUsing(function (Produk $record, mixed $state): mixed {
                app(AdminCatalog::class)->moveProducts([$record->id], $state);
                Notification::make()->title('Kategori diperbarui')->success()->send();

                return $state;
            }),
            TextColumn::make('harga')->label('Harga')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))->sortable(),
            ToggleColumn::make('aktif')->label('Aktif')->disabled(fn (): bool => ! static::canViewAny())->updateStateUsing(fn (Produk $record, bool $state): bool => app(AdminCatalog::class)->setActive($record, $state)),
        ])->filters([SelectFilter::make('kategori_id')->label('Kategori')->relationship('kategori', 'nama'), TernaryFilter::make('aktif')->label('Aktif')->trueLabel('Aktif')->falseLabel('Nonaktif')])->recordActions([EditAction::make()->label('Ubah'), Action::make('delete')->label('Hapus')->color('danger')->disabled()->tooltip('Sudah dipakai di pesanan, nonaktifkan saja')])
            ->selectCurrentPageOnly(false)->toolbarActions([BulkAction::make('moveCategory')->label('Pindahkan kategori')->schema([
                Select::make('kategori_id')->label('Kategori tujuan')->options(fn (): array => Kategori::query()->orderBy('nama')->pluck('nama', 'id')->all())->required()->rules(['exists:kategoris,id'])->searchable(),
            ])->action(function (Collection $records, array $data): void {
                app(AdminCatalog::class)->moveProducts($records->modelKeys(), $data['kategori_id']);
                Notification::make()->title('Kategori diperbarui')->success()->send();
            })->deselectRecordsAfterCompletion()])->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada produk yang sesuai')->defaultSort('nama');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('kategori');
    }

    public static function getPages(): array
    {
        return ['index' => ListProduks::route('/'), 'create' => CreateProduk::route('/create'), 'edit' => EditProduk::route('/{record}/edit')];
    }
}
