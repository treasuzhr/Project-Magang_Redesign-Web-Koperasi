<?php

namespace App\Filament\Resources\Beritas;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Beritas\Pages\CreateBerita;
use App\Filament\Resources\Beritas\Pages\EditBerita;
use App\Filament\Resources\Beritas\Pages\ListBeritas;
use App\Models\Berita;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BeritaResource extends AdminResource
{
    protected static ?string $model = Berita::class;

    protected static ?string $slug = 'berita';

    protected static ?string $modelLabel = 'Berita';

    protected static ?string $pluralModelLabel = 'Berita';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

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
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')->label('Judul')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true), Textarea::make('ringkasan')->required()->maxLength(5000),
            RichEditor::make('isi')->label('Isi')->toolbarButtons([['bold', 'italic', 'h2', 'h3'], ['bulletList', 'orderedList', 'link'], ['undo', 'redo']])->required()->columnSpanFull(),
            FileUpload::make('foto')->label('Gambar sampul')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(2048)->disk('public')->directory('news')->visibility('public')->preventFilePathTampering(),
            Toggle::make('is_published')->label('Terbit')->default(false)->live(),
            DateTimePicker::make('published_at')->label('Tanggal tayang')->default(now())->required(fn (Get $get): bool => (bool) $get('is_published'))->visible(fn (Get $get): bool => (bool) $get('is_published')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('judul')->searchable()->wrap(),
            TextColumn::make('status')->state(fn (Berita $record): string => $record->published_at === null ? 'Draf' : ($record->published_at->isFuture() ? 'Terjadwal' : 'Terbit'))->badge()->color(fn (string $state): string => match ($state) {
                'Terbit' => 'success','Terjadwal' => 'info',default => 'gray'
            }),
            TextColumn::make('published_at')->label('Tanggal tayang')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
        ])->recordActions([
            EditAction::make()->label('Ubah'),
            ActionGroup::make([DeleteAction::make()->label('Hapus')->authorize(fn (): bool => static::canViewAny())])
                ->icon(Heroicon::OutlinedEllipsisVertical)->tooltip('Aksi lainnya'),
        ])->recordActionsColumnLabel('Aksi')->recordActionsAlignment('end')->defaultSort('published_at', 'desc')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada berita pada tab ini');
    }

    public static function getPages(): array
    {
        return ['index' => ListBeritas::route('/'), 'create' => CreateBerita::route('/create'), 'edit' => EditBerita::route('/{record}/edit')];
    }
}
