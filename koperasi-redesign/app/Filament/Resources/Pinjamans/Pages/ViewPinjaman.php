<?php

namespace App\Filament\Resources\Pinjamans\Pages;

use App\Filament\Resources\Pinjamans\PinjamanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewPinjaman extends ViewRecord
{
    protected static string $resource = PinjamanResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(fn (Action $action): Action => $action->after(fn () => $this->dispatch('loan-updated')), PinjamanResource::workflowActions());
    }

    #[On('loan-updated')]
    public function refreshLoan(): void
    {
        $this->getRecord()->refresh();
    }
}
