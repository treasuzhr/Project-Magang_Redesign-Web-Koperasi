<?php

namespace App\Filament\Resources\Kategoris;

use App\AdminCatalog;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Kategoris\Pages\CreateKategori;
use App\Filament\Resources\Kategoris\Pages\EditKategori;
use App\Filament\Resources\Kategoris\Pages\ListKategoris;
use App\Models\Kategori;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KategoriResource extends AdminResource
{
    protected static ?string $model = Kategori::class;

    protected static ?string $slug = 'kategori';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && ((int) ($record->produks_count ?? $record->produks()->count()) === 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->label('Nama')->required()->maxLength(255)
                ->rules([fn (?Kategori $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    $slug = Str::slug((string) $value);
                    if ($slug === '') {
                        $fail('Nama kategori harus menghasilkan slug yang valid.');

                        return;
                    }
                    if (Kategori::query()->where('slug', $slug)->when($record !== null, fn (Builder $query): Builder => $query->whereKeyNot($record->id))->exists()) {
                        $fail('Slug kategori sudah digunakan.');
                    }
                }])->live(onBlur: true)->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')->label('Slug')->disabled()->dehydrated(false),
        ]);
    }

    public static function deletionAction(): DeleteAction
    {
        return DeleteAction::make()->label('Hapus')->disabled(fn (Kategori $record): bool => ! static::canDelete($record))->tooltip(fn (Kategori $record): ?string => ($count = (int) ($record->produks_count ?? $record->produks()->count())) > 0 ? 'Masih punya '.$count.' produk' : null)->authorize(fn (Kategori $record): bool => static::canViewAny())->using(function (Kategori $record): bool {
            app(AdminCatalog::class)->deleteCategory($record);

            return true;
        });
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('nama')->label('Nama')->searchable(), TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true), TextColumn::make('produks_count')->label('Jumlah produk')])->recordActions([EditAction::make()->label('Ubah'), static::deletionAction(),
            Action::make('mergeCategory')->label('Gabungkan ke kategori lain')->requiresConfirmation()
                ->modalDescription(fn (Kategori $record): string => 'Sebanyak '.$record->produks()->count().' produk akan dipindah dan kategori asal dihapus.')
                ->schema([Select::make('kategori_id')->label('Kategori tujuan')->options(fn (Kategori $record): array => Kategori::query()->whereKeyNot($record->id)->orderBy('nama')->pluck('nama', 'id')->all())->required()->rules(fn (Kategori $record): array => ['exists:kategoris,id', Rule::notIn([$record->id])])])
                ->action(fn (Kategori $record, array $data): int => app(AdminCatalog::class)->mergeCategory($record, $data['kategori_id']))->successNotificationTitle('Kategori digabungkan'),
        ])->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada kategori');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('produks');
    }

    public static function getPages(): array
    {
        return ['index' => ListKategoris::route('/'), 'create' => CreateKategori::route('/create'), 'edit' => EditKategori::route('/{record}/edit')];
    }
}
