<?php

namespace App\Filament\Resources\SimpananTransaksis;

use App\AdminSavings;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\SimpananTransaksis\Pages\ListSimpananTransaksis;
use App\Models\Anggota;
use App\Models\SimpananTransaksi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SimpananTransaksiResource extends AdminResource
{
    protected static ?string $model = SimpananTransaksi::class;

    protected static ?string $slug = 'simpanan';

    protected static ?string $modelLabel = 'Transaksi Simpanan';

    protected static ?string $pluralModelLabel = 'Transaksi Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public static function validateAction(Component $livewire, \Closure $operation, ?string $fallback = null): mixed
    {
        try {
            return $operation();
        } catch (ValidationException $exception) {
            $messages = [];
            foreach ($exception->errors() as $field => $errors) {
                $messages[$livewire->getSchema($livewire->getMountedActionSchemaName())->getStatePath().'.'.($fallback ?? $field)] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }

    public static function transactionFields(): array
    {
        return [Select::make('anggota_id')->label('Anggota')->required()->searchable()
            ->getSearchResultsUsing(fn (string $search): array => Anggota::query()->with('user')->where(fn (Builder $query) => $query->where('nama', 'like', '%'.$search.'%')->orWhereHas('user', fn (Builder $user) => $user->where('nip', 'like', '%'.$search.'%')))->limit(30)->get()->mapWithKeys(fn (Anggota $member): array => [$member->id => $member->nama.' / '.$member->user->nip])->all())
            ->getOptionLabelUsing(fn ($value): ?string => Anggota::with('user')->find($value)?->nama),
            Select::make('jenis')->options(array_combine(SimpananTransaksi::JENIS, array_map('ucfirst', SimpananTransaksi::JENIS)))->required(),
            Select::make('tipe')->options(['setor' => 'Setor', 'tarik' => 'Tarik'])->required(),
            TextInput::make('nominal')->label('Jumlah (Rp)')->numeric()->rules(['integer'])->minValue(1)->required(),
            DatePicker::make('tanggal')->required()->maxDate(today())->default(today()), Textarea::make('keterangan')->maxLength(2000)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('tanggal')->date('d M Y')->sortable(), TextColumn::make('anggota.nama')->label('Anggota')->searchable(),
            TextColumn::make('anggota.user.nip')->label('NIP/NRP')->searchable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('jenis')->formatStateUsing(fn (string $state): string => ucfirst($state)), TextColumn::make('tipe')->badge()->color(fn (string $state): string => $state === 'setor' ? 'success' : 'warning')->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextColumn::make('nominal')->label('Jumlah')->alignEnd()->formatStateUsing(fn (int $state): string => 'Rp '.number_format($state, 0, ',', '.')),
            TextColumn::make('keterangan')->wrap()->placeholder('-')->toggleable()];
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::columns())->filters([
            SelectFilter::make('jenis')->options(array_combine(SimpananTransaksi::JENIS, array_map('ucfirst', SimpananTransaksi::JENIS))), SelectFilter::make('tipe')->options(['setor' => 'Setor', 'tarik' => 'Tarik']),
            Filter::make('tanggal')->schema([DatePicker::make('dari'), DatePicker::make('sampai')])->query(fn (Builder $query, array $data): Builder => $query->when($data['dari'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('tanggal', '>=', $date))->when($data['sampai'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('tanggal', '<=', $date))),
        ])->recordActions([Action::make('correct')->label('Koreksi')->modalDescription('Membuat transaksi lawan dengan jumlah yang sama. Catatan asli tetap disimpan.')->schema([Textarea::make('keterangan')->label('Alasan koreksi')->required()->maxLength(1800)])
            ->action(fn (SimpananTransaksi $record, array $data, Component $livewire): SimpananTransaksi => static::validateAction($livewire, fn (): SimpananTransaksi => app(AdminSavings::class)->correct($record, $data['keterangan']), 'keterangan'))->successNotificationTitle('Koreksi dicatat')])
            ->recordActionsColumnLabel('Aksi')->recordActionsAlignment('end')->defaultSort('tanggal', 'desc')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada transaksi simpanan yang sesuai');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('anggota.user');
    }

    public static function getPages(): array
    {
        return ['index' => ListSimpananTransaksis::route('/')];
    }
}
