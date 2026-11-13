<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPesanan extends ViewRecord
{
    protected static string $resource = PesananResource::class;

    protected function getHeaderActions(): array
    {
        return PesananResource::workflowActions();
    }
}
