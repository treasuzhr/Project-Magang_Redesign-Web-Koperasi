<?php

namespace App\Filament\Resources\Dokumens;

use App\AdminDocuments;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Dokumens\Pages\CreateDokumen;
use App\Filament\Resources\Dokumens\Pages\EditDokumen;
use App\Filament\Resources\Dokumens\Pages\ListDokumens;
use App\MemberOverview;
use App\Models\Dokumen;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DokumenResource extends AdminResource
{
    protected static ?string $model = Dokumen::class;

    protected static ?string $slug = 'dokumen';

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

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
            TextInput::make('judul')->required()->maxLength(255), TextInput::make('tahun')->label('Tahun')->numeric()->rules(['integer'])->minValue(1)->maxValue(65535)->default(now()->year)->required(),
            FileUpload::make('file_path')->label('Berkas')->required(fn (string $operation): bool => $operation === 'create')->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'])->rules(['extensions:pdf,docx,xlsx'])->maxSize(10240)->disk('local')->directory('documents')->visibility('private')->preventFilePathTampering()->previewable(false)->openable(false)->downloadable(false)->helperText('PDF, DOCX, atau XLSX. Maksimal 10 MB.')
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => (string) Str::uuid().'.'.strtolower($file->getClientOriginalExtension()))
                ->getUploadedFileUsing(function (string $file): ?array {
                    $path = app(MemberOverview::class)->privateDocumentPath($file);

                    return $path === null ? null : ['name' => basename($path), 'size' => Storage::disk('local')->size($path), 'type' => Storage::disk('local')->mimeType($path), 'url' => null];
                }),
            Toggle::make('published')->label('Terbit')->default(false)->required(),
        ])->columns(2);
    }

    public static function deletionAction(): DeleteAction
    {
        return DeleteAction::make()->label('Hapus')->authorize(fn (): bool => static::canViewAny())->using(fn (Dokumen $record): bool => app(AdminDocuments::class)->delete($record));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('judul')->searchable()->wrap(), TextColumn::make('tahun')->sortable(), TextColumn::make('jenis_berkas')->label('Jenis berkas')->state(fn (Dokumen $record): string => app(AdminDocuments::class)->metadata($record)['type']), TextColumn::make('ukuran')->state(fn (Dokumen $record): string => app(AdminDocuments::class)->metadata($record)['size']),
            ToggleColumn::make('published')->label('Terbit')->disabled(fn (): bool => ! static::canViewAny())->updateStateUsing(fn (Dokumen $record, bool $state): bool => app(AdminDocuments::class)->publish($record, $state)), TextColumn::make('created_at')->label('Ditambahkan')->date('d M Y')->sortable(),
        ])->recordActions([EditAction::make()->label('Ubah'), static::deletionAction()])->defaultSort('tahun', 'desc')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada dokumen pada tab ini');
    }

    public static function getPages(): array
    {
        return ['index' => ListDokumens::route('/'), 'create' => CreateDokumen::route('/create'), 'edit' => EditDokumen::route('/{record}/edit')];
    }
}
