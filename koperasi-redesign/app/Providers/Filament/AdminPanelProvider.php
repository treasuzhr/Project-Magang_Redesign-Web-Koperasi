<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Anggotas\AnggotaResource;
use App\Filament\Resources\Kategoris\KategoriResource;
use App\Filament\Resources\Pesanans\PesananResource;
use App\Filament\Resources\Pinjamans\PinjamanResource;
use App\Filament\Resources\Produks\ProdukResource;
use App\Filament\Widgets\AdminStats;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequireRole;
use App\Http\Responses\AdminLogoutResponse;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
        $this->app->bind(LogoutResponse::class, AdminLogoutResponse::class);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Primkop Admin')
            ->brandLogo(fn () => view('filament.admin.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.svg'))
            ->darkMode(false)
            ->font('Figtree')
            ->sidebarCollapsibleOnDesktop()
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $builder->items([
                ...array_map(fn (NavigationItem $item): NavigationItem => $item->icon(Heroicon::OutlinedHome), Dashboard::getNavigationItems()),
                ...(AnggotaResource::canAccess() ? array_map(fn (NavigationItem $item): NavigationItem => $item->icon(Heroicon::OutlinedUsers), AnggotaResource::getNavigationItems()) : []),
                ...(PinjamanResource::canAccess() ? PinjamanResource::getNavigationItems() : []),
                ...(PesananResource::canAccess() ? PesananResource::getNavigationItems() : []),
                ...(ProdukResource::canAccess() ? ProdukResource::getNavigationItems() : []),
                ...(KategoriResource::canAccess() ? KategoriResource::getNavigationItems() : []),
            ]))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->userMenuItems(['logout' => fn (Action $action) => $action->label('Keluar')])
            ->colors([
                'primary' => Color::hex('#4C7A10'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                RequireRole::class.':admin',
                RequirePasswordChange::class,
                Authenticate::class,
            ], isPersistent: true);
    }
}
