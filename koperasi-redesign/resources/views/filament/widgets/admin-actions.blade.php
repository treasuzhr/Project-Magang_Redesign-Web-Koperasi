<x-filament-widgets::widget>
    <div
        class="primkop-action-widget"
        x-data="{
            focusTab(direction) {
                const tabs = Array.from($el.querySelectorAll('[role=tab]'));
                const current = tabs.indexOf(document.activeElement);
                const next = tabs[(current + direction + tabs.length) % tabs.length];
                next.focus();
                next.click();
            },
            focusBoundary(last) {
                const tabs = Array.from($el.querySelectorAll('[role=tab]'));
                const next = last ? tabs[tabs.length - 1] : tabs[0];
                next.focus();
                next.click();
            },
        }"
    >
        <x-filament::section heading="Perlu tindakan" class="primkop-action-section">
            <x-slot name="afterHeader">
                <div class="primkop-action-tabs" role="tablist" aria-label="Daftar yang perlu tindakan">
                    @foreach ($tabs as $key => $tab)
                        <button
                            id="admin-action-tab-{{ $key }}"
                            type="button"
                            role="tab"
                            aria-controls="admin-action-panel"
                            aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                            tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                            @class(['primkop-action-tab', 'is-active' => $activeTab === $key])
                            wire:click="selectTab('{{ $key }}')"
                            @keydown.arrow-right.prevent="focusTab(1)"
                            @keydown.arrow-left.prevent="focusTab(-1)"
                            @keydown.home.prevent="focusBoundary(false)"
                            @keydown.end.prevent="focusBoundary(true)"
                        >
                            <span>{{ $tab['label'] }}</span>
                            <span class="primkop-action-tab-count">{{ number_format($counts[$key], 0, ',', '.') }}</span>
                        </button>
                    @endforeach
                </div>
            </x-slot>

            <div
                id="admin-action-panel"
                @class(['primkop-action-panel', 'is-empty' => $items === []])
                role="tabpanel"
                aria-labelledby="admin-action-tab-{{ $activeTab }}"
                aria-live="polite"
                wire:key="admin-action-panel-{{ $activeTab }}"
            >
                @if ($items === [])
                    <p class="primkop-action-empty">{{ $tabs[$activeTab]['empty'] }}</p>
                @else
                    <ul class="primkop-action-list">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ $item['url'] }}">
                                    <span>
                                        <strong>{{ $item['primary'] }}</strong>
                                        <small>{{ $item['secondary'] }}</small>
                                    </span>
                                    <b>Rp {{ number_format($item['amount'], 0, ',', '.') }}</b>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($counts[$activeTab] > count($items))
                    <a class="primkop-action-all" href="{{ $tabs[$activeTab]['all_url'] }}">
                        Lihat semua ({{ number_format($counts[$activeTab], 0, ',', '.') }})
                    </a>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
