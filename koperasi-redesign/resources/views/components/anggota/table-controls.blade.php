@props(['tableId', 'total', 'position'])

@if ($position === 'top')
    <div class="member-table-controls member-table-controls--top">
        <label for="{{ $tableId }}-length">Tampilkan
            <select id="{{ $tableId }}-length" data-table-length disabled>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            data
        </label>
    </div>
@else
    <div class="member-table-controls member-table-controls--bottom">
        <p class="member-table-info" data-table-info>
            Menampilkan {{ $total === 0 ? 0 : 1 }}-{{ $total }} dari {{ $total }} data
        </p>
        <nav class="member-pagination" data-table-pagination aria-label="Halaman tabel" hidden>
            <button type="button" class="member-pagination__button" data-page-previous aria-label="Halaman sebelumnya" disabled>Sebelumnya</button>
            <span class="member-pagination__pages" data-page-numbers></span>
            <button type="button" class="member-pagination__button" data-page-next aria-label="Halaman berikutnya" disabled>Berikutnya</button>
        </nav>
    </div>
@endif
