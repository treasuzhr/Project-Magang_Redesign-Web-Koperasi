<li class="org-chart__node org-chart__node--depth-{{ $depth }}">
    <article class="org-person">
        <div class="org-person__main">
            <img class="org-person__photo" src="{{ asset($person['photo']) }}" alt="">
            <div class="org-person__identity">
                <h3 id="org-person-{{ $person['id'] }}">{{ $person['name'] }}</h3>
                <p>{{ $person['position'] }}</p>
            </div>
        </div>
        @if ($person['reports_to'])
            <p class="org-person__report">
                <span>Melapor kepada</span>
                <strong>{{ $person['reports_to'] }}</strong>
            </p>
        @endif
    </article>

    @if ($person['children'])
        <ul class="org-chart__children org-chart__children--depth-{{ $depth + 1 }}" aria-labelledby="org-person-{{ $person['id'] }}">
            @foreach ($person['children'] as $child)
                @include('components.organization-node', ['person' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
