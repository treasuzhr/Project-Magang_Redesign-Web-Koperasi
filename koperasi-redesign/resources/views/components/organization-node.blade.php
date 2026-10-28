@php
    $hasPhoto = ! empty($person['photo']) && is_file(public_path($person['photo']));
    $initials = collect(explode(' ', explode(',', $person['name'])[0]))->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
    $filter = $person['position'] === 'Pengawas' ? 'pengawas' : $group;
@endphp
<li class="about-member home-reveal" data-person-id="{{ $person['id'] }}" data-person-filter="{{ $filter }}" data-parent-id="{{ $parentId }}" style="--reveal-delay: {{ ($loop->index % 4) * 100 }}ms">
    <article class="org-person" id="org-person-{{ $person['id'] }}" tabindex="-1">
        <figure class="org-person__portrait">
            <button class="org-person__open" type="button" data-person-open aria-label="Lihat detail {{ $person['name'] }}, {{ $person['position'] }}" aria-haspopup="dialog" aria-controls="about-person-dialog">
                @if ($hasPhoto)
                    <img src="{{ asset($person['photo']) }}" alt="{{ $person['name'] }} — {{ $person['position'] }}" loading="lazy">
                @else
                    <span class="org-person__initials" aria-hidden="true">{{ $initials }}</span>
                @endif
                <span class="org-person__detail-label" aria-hidden="true">Lihat profil</span>
            </button>
            @if ($person['reports_to'])
                <p class="org-person__report"><span>Melapor kepada</span><strong>{{ $person['reports_to'] }}</strong></p>
            @endif
        </figure>
        <div class="org-person__identity"><h4>{{ $person['name'] }}</h4><p>{{ $person['position'] }}</p></div>
    </article>
</li>
