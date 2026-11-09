@props(['tabs'])

<nav class="member-tabs" aria-label="Jenis simpanan">
    <div class="member-tabs__scroll" data-member-tabs>
        <div class="member-tabs__track">
            <span class="member-tabs__indicator" aria-hidden="true"></span>
            @foreach ($tabs as $tab)
                <a
                    class="member-tab {{ $tab['active'] ? 'is-active' : '' }}"
                    href="{{ $tab['url'] }}"
                    data-member-tab
                    @if ($tab['active']) aria-current="page" @endif
                >{{ $tab['label'] }}</a>
            @endforeach
        </div>
    </div>
</nav>
