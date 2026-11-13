<?php

namespace App\Filament\Resources\Pinjamans;

use App\AdminLoans;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Pinjamans\Pages\ListPinjamans;
use App\Filament\Resources\Pinjamans\Pages\ViewPinjaman;
use App\Filament\Resources\Pinjamans\RelationManagers\AngsuransRelationManager;
use App\MemberOverview;
use App\Models\Pinjaman;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PinjamanResource extends AdminResource
{
    protected static ?string $model = Pinjaman::class;

    protected static ?string $slug = 'pinjaman';

    protected static ?string $modelLabel = 'Pinjaman';

    protected static ?string $pluralModelLabel = 'Pinjaman';

    protected static ?string $recordTitleAttribute = 'kode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getNavigationBadge(): ?string
    {
        return (string) Pinjaman::query()->where('status', 'diajukan')->count();
    }

    public static function workflowActions(): array
    {
        return [
            Action::make('approve')->label('Setujui')->requiresConfirmation()->visible(fn (Pinjaman $record): bool => $record->status === 'diajukan')
                ->action(fn (Pinjaman $record): Pinjaman => app(AdminLoans::class)->approve($record))->successNotificationTitle('Pengajuan disetujui.'),
            Action::make('reject')->label('Tolak')->color('danger')->visible(fn (Pinjaman $record): bool => $record->status === 'diajukan')
                ->schema([Textarea::make('catatan_admin')->label('Catatan penolakan')->required()->maxLength(10000)])
                ->action(fn (Pinjaman $record, array $data): Pinjaman => app(AdminLoans::class)->reject($record, $data['catatan_admin']))->successNotificationTitle('Pengajuan ditolak.'),
            Action::make('disburse')->label('Cairkan')->requiresConfirmation()->visible(fn (Pinjaman $record): bool => $record->status === 'disetujui')
                ->action(fn (Pinjaman $record): Pinjaman => app(AdminLoans::class)->disburse($record))->successNotificationTitle('Pinjaman dicairkan dan jadwal angsuran dibuat.'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('kode')->label('Kode')->searchable(),
            TextColumn::make('anggota.nama')->label('Anggota')->searchable(),
            TextColumn::make('anggota.user.nip')->label('NIP/NRP')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            TextColumn::make('jenis.nama')->label('Jenis'),
            TextColumn::make('jumlah')->label('Jumlah')->alignEnd()->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            TextColumn::make('tenor_bulan')->label('Tenor (bulan)'),
            TextColumn::make('metode_bayar')->label('Metode bayar')->toggleable(isToggledHiddenByDefault: true)->formatStateUsing(fn (string $state): string => $state === 'potong_gaji' ? 'Potong Gaji' : 'Tunjangan Kinerja'),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'diajukan' => 'warning', 'disetujui' => 'info', 'berjalan' => 'success', 'ditolak' => 'danger', default => 'gray'
            })->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextColumn::make('tanggal_pengajuan')->label('Tanggal pengajuan')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y'))->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(array_combine(Pinjaman::STATUSES, array_map('ucfirst', Pinjaman::STATUSES))),
            SelectFilter::make('pinjaman_jenis_id')->label('Jenis')->relationship('jenis', 'nama'),
        ])->recordActions([ViewAction::make()->label('Detail'), ...static::workflowActions()])->defaultPaginationPageOption(25)->emptyStateHeading(fn ($livewire): string => $livewire->activeTab === 'diajukan' ? 'Belum ada pengajuan yang perlu diproses' : 'Tidak ada data pada antrean ini')->defaultSort('tanggal_pengajuan', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('kode')->label('Kode'), TextEntry::make('anggota.nama')->label('Anggota'),
            TextEntry::make('anggota.user.nip')->label('NIP/NRP'), TextEntry::make('jenis.nama')->label('Jenis'),
            TextEntry::make('jumlah')->label('Jumlah')->formatStateUsing(fn (mixed $state): string => 'Rp '.number_format((float) $state, 0, ',', '.')), TextEntry::make('tenor_bulan')->label('Tenor (bulan)'),
            TextEntry::make('bunga_persen_per_bulan')->label('Bunga (% per bulan)'),
            TextEntry::make('metode_bayar')->label('Metode bayar')->formatStateUsing(fn (string $state): string => $state === 'potong_gaji' ? 'Potong Gaji' : 'Tunjangan Kinerja'),
            TextEntry::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'diajukan' => 'warning', 'disetujui' => 'info', 'berjalan' => 'success', 'ditolak' => 'danger', default => 'gray'
            })->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextEntry::make('tanggal_pengajuan')->label('Tanggal pengajuan')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y')),
            TextEntry::make('tanggal_disetujui')->label('Tanggal disetujui')->formatStateUsing(fn (mixed $state): string => Carbon::parse($state)->locale('id')->translatedFormat('d M Y'))->placeholder('-'),
            TextEntry::make('catatan_admin')->label('Catatan admin')->placeholder('-'),
            TextEntry::make('akad')->label('Dokumen akad')->state(fn (Pinjaman $record): string => app(MemberOverview::class)->privateDocumentPath($record->dokumen_akad_path) ? 'Unduh dokumen akad' : 'Dokumen akad belum tersedia.')
                ->url(fn (Pinjaman $record): ?string => app(MemberOverview::class)->privateDocumentPath($record->dokumen_akad_path) ? route('admin.pinjaman.akad', $record) : null),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user', 'jenis']);
    }

    public static function getRelations(): array
    {
        return [AngsuransRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListPinjamans::route('/'), 'view' => ViewPinjaman::route('/{record}')];
    }
}
