<?php

namespace App\Filament\Resources\Anggotas\RelationManagers;

use App\Filament\Resources\SimpananTransaksis\SimpananTransaksiResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SimpananTransaksisRelationManager extends RelationManager
{
    protected static string $relationship = 'simpananTransaksis';

    protected static ?string $title = 'Riwayat transaksi simpanan';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table->columns(SimpananTransaksiResource::columns())->recordActions([])->toolbarActions([])->defaultSort('tanggal', 'desc')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada transaksi simpanan');
    }
}
