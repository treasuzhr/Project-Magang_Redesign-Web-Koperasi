<?php

namespace App\Filament\Widgets;

use App\AdminOverview;
use Filament\Widgets\Widget;

class AdminActions extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-actions';

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getViewData(): array
    {
        return ['actions' => app(AdminOverview::class)->actions()];
    }
}
