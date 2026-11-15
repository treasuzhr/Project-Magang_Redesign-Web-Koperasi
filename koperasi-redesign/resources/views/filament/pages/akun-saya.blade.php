<x-filament-panels::page>
    <x-filament::section class="max-w-xl">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">Ganti password</x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
