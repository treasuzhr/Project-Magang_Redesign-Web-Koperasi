<?php

namespace App\Filament\Resources\Anggotas;

use App\AdminMemberAccounts;
use App\Filament\Resources\Anggotas\Pages\CreateAnggota;
use App\Filament\Resources\Anggotas\Pages\EditAnggota;
use App\Filament\Resources\Anggotas\Pages\ListAnggotas;
use App\Filament\Resources\Anggotas\Pages\ViewAnggota;
use App\Filament\Resources\Anggotas\RelationManagers\SimpananTransaksisRelationManager;
use App\MemberOverview;
use App\Models\Anggota;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AnggotaResource extends Resource
{
    protected static ?string $model = Anggota::class;

    protected static ?string $slug = 'anggota';

    protected static ?string $modelLabel = 'Anggota';

    protected static ?string $pluralModelLabel = 'Anggota';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nomor_anggota')->label('Nomor anggota')->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(255),
            TextInput::make('nip')->label('NIP/NRP')->default(null)->autocomplete('off')
                ->extraInputAttributes(['name' => 'cooperative_member_reference', 'data-form-type' => 'other'])
                ->required()->maxLength(255)
                ->unique(table: 'users', column: 'nip', ignorable: fn (?Anggota $record) => $record?->user, ignoreRecord: false)
                ->validationMessages(['unique' => 'NIP sudah digunakan.']),
            TextInput::make('password')->label('Kata sandi awal')->default(null)->password()->autocomplete('new-password')
                ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true', 'data-bwignore' => 'true'])
                ->revealable()->minLength(8)->maxLength(1024)
                ->required(fn (string $operation): bool => $operation === 'create')->visibleOn('create')->dehydrated(fn (string $operation): bool => $operation === 'create'),
            TextInput::make('pangkat_golongan')->label('Pangkat / golongan')->maxLength(255),
            DatePicker::make('tanggal_masuk')->label('Tanggal masuk')->required(),
            DatePicker::make('tanggal_keluar')->label('Tanggal keluar')
                ->helperText('Isi jika anggota berhenti (pensiun, pindah, atau mengundurkan diri). Kosongkan jika masih aktif.')
                ->required(fn (Get $get): bool => $get('status') !== 'aktif')
                ->afterOrEqual('tanggal_masuk')
                ->validationMessages([
                    'after_or_equal' => 'Tanggal keluar tidak boleh lebih awal dari tanggal masuk.',
                    'required' => 'Tanggal keluar wajib diisi jika status anggota tidak aktif.',
                ]),
            Select::make('status')->label('Status')->options(array_combine(Anggota::STATUSES, array_map('ucfirst', Anggota::STATUSES)))
                ->default('aktif')->required()->live()
                ->rules(fn (Get $get): array => filled($get('tanggal_keluar')) ? ['not_in:aktif'] : [])
                ->validationMessages(['not_in' => 'Pilih status nonaktif jika tanggal keluar diisi.']),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nomor_anggota')->label('Nomor anggota')->searchable()->placeholder('-'),
            TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
            TextColumn::make('user.nip')->label('NIP/NRP')->searchable(),
            TextColumn::make('pangkat_golongan')->label('Pangkat / golongan')->toggleable(isToggledHiddenByDefault: true)->placeholder('-'),
            TextColumn::make('tanggal_masuk')->label('Tanggal masuk')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y'))->sortable(),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))
                ->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
        ])->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada anggota yang sesuai')->defaultSort('nama')->filters([
            SelectFilter::make('status')->label('Status')->options(array_combine(Anggota::STATUSES, array_map('ucfirst', Anggota::STATUSES))),
        ])->recordActions([
            ViewAction::make()->label('Detail'),
            EditAction::make()->label('Ubah'),
            Action::make('resetPassword')->label('Atur ulang kata sandi')->requiresConfirmation()
                ->modalDescription('Buat kata sandi sementara baru. Anggota wajib menggantinya saat mengakses akun.')
                ->action(function (Anggota $record): void {
                    $password = app(AdminMemberAccounts::class)->resetPassword($record);
                    Notification::make()->title('Kata sandi sementara: '.$password)->body('Sampaikan kepada anggota secara pribadi. Anggota wajib mengganti kata sandi.')->success()->persistent()->send();
                }),
        ])->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user')->whereHas('user', fn (Builder $query) => $query->where('role', 'anggota'));
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('nama')->label('Nama'),
            TextEntry::make('user.nip')->label('NIP/NRP'),
            RepeatableEntry::make('saldo')->label('Saldo simpanan')->state(fn (Anggota $record): array => collect(app(MemberOverview::class)->savings($record)['summary'])->map(fn (int $saldo, string $jenis): array => ['jenis' => ucfirst($jenis), 'saldo' => $saldo])->values()->all())->schema([
                TextEntry::make('jenis'),
                TextEntry::make('saldo')->formatStateUsing(fn (int $state): string => 'Rp '.number_format($state, 0, ',', '.')),
            ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [SimpananTransaksisRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListAnggotas::route('/'), 'create' => CreateAnggota::route('/create'), 'edit' => EditAnggota::route('/{record}/edit'), 'view' => ViewAnggota::route('/{record}')];
    }
}
