<?php

namespace App\Filament\Resources\ShuPeriodes;

use App\AdminShu;
use App\Filament\Resources\AdminResource;
use App\Models\ShuPeriode;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class ShuPeriodeResource extends AdminResource
{
    protected static ?string $model = ShuPeriode::class;

    protected static ?string $slug = 'shu';

    protected static ?string $modelLabel = 'SHU';

    protected static ?string $pluralModelLabel = 'SHU';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && $record->status === 'draft';
    }

    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return static::canEdit($record) ? Response::allow() : Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        $fields = [TextInput::make('tahun')->label('Tahun buku')->numeric()->required()->unique(ignoreRecord: true)];
        foreach (AdminShu::POOLS as $key => $label) {
            $fields[] = TextInput::make('pool_'.$key)->label('Pool '.$label)->numeric()->minValue(0)->required()->prefix('Rp');
        }
        foreach (AdminShu::PERCENTAGES as $key => $label) {
            $fields[] = TextInput::make($key)->label($label)->numeric()->minValue(0)->maxValue(100)->required()->default($key === 'jasa_tetap' ? 27.5 : null);
        }
        $fields[] = Select::make('dasar_saldo')->label('Dasar saldo simpanan')->options(['akhir_tahun' => 'Saldo akhir tahun', 'rata_bulanan' => 'Rata-rata saldo akhir bulan Jan-Des'])->default('akhir_tahun')->required();

        return $schema->components($fields);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('tahun')->label('Tahun buku')->sortable(),
            TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => $state === 'draft' ? 'Draf' : 'Ditetapkan')->color(fn (string $state): string => $state === 'draft' ? 'warning' : 'success'),
            TextColumn::make('shu_bersih')->label('Total terbagi')->placeholder('Belum dihitung')->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))->alignEnd(),
            TextColumn::make('ditetapkan_at')->label('Tanggal penetapan')->dateTime('d M Y H:i')->placeholder('-'),
        ])->recordActions([ViewAction::make()->label('Pratinjau'), EditAction::make()->label('Ubah')])
            ->recordActionsColumnLabel('Aksi')->recordActionsAlignment('end')->defaultSort('tahun', 'desc')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada periode SHU');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListShuPeriodes::route('/'), 'create' => Pages\CreateShuPeriode::route('/create'), 'edit' => Pages\EditShuPeriode::route('/{record}/edit'), 'view' => Pages\PreviewShuPeriode::route('/{record}')];
    }
}
