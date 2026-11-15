<?php

namespace App\Filament;

use Filament\Livewire\Topbar;
use Illuminate\Contracts\View\View;

class AdminTopbar extends Topbar
{
    public function render(): View
    {
        return view('filament.admin.topbar');
    }
}
