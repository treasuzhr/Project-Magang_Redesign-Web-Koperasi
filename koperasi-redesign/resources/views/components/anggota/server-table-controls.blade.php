@props(['tableId', 'paginator', 'position'])

@if ($position === 'top')
    <form class="member-table-controls member-table-controls--top" method="GET" action="{{ url()->current() }}">
        @foreach (request()->only(['jenis', 'tipe', 'tahun', 'status']) as $key => $value)
            @if ($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="{{ $tableId }}-length">Tampilkan
            <select id="{{ $tableId }}-length" name="per_page">
                @foreach ([10, 25, 50] as $size)
                    <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }}</option>
                @endforeach
            </select>
            data
        </label>
        <button type="submit" class="member-pagination__button">Terapkan</button>
    </form>
@else
    <div class="member-table-controls member-table-controls--bottom">
        <p class="member-table-info">Menampilkan {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data</p>
        <nav class="member-pagination" aria-label="Halaman tabel">
            @if ($paginator->onFirstPage())
                <span class="member-pagination__button" aria-disabled="true">Sebelumnya</span>
            @else
                <a class="member-pagination__button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
            @endif
            <span class="member-pagination__pages">
                @foreach (Illuminate\Pagination\UrlWindow::make($paginator->onEachSide(1)) as $urls)
                    @if (is_array($urls))
                        @foreach ($urls as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <span class="member-pagination__page is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="member-pagination__page" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @elseif ($paginator->hasPages())
                        <span aria-hidden="true">...</span>
                    @endif
                @endforeach
            </span>
            @if ($paginator->hasMorePages())
                <a class="member-pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
            @else
                <span class="member-pagination__button" aria-disabled="true">Berikutnya</span>
            @endif
        </nav>
    </div>
@endif
