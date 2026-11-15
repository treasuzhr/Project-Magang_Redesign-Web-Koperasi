@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;
@endphp
<div x-data="filamentSidebarBackground" x-bind:inert="$store.sidebar.isOpenMobile && ! hasOpenModal" class="fi-topbar-ctn">
    <nav aria-label="Navigasi admin" class="fi-topbar primkop-admin-topbar">
        {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_START) }}
        <button type="button" class="primkop-sidebar-toggle" aria-label="Buka atau tutup sidebar" aria-controls="fi-main-sidebar" x-bind:aria-expanded="$store.sidebar.isOpen" x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open($event.currentTarget)">
            <x-filament::icon :icon="Heroicon::OutlinedBars3" class="h-6 w-6" />
        </button>
        <div class="fi-topbar-end">
            {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE) }}
            @if(filament()->isGlobalSearchEnabled())
                @livewire(\Filament\Livewire\GlobalSearch::class)
            @endif
            {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER) }}
            <x-filament-panels::user-menu />
        </div>
        {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_END) }}
    </nav>
    <x-filament-actions::modals />
</div>
