@props(['tableId', 'caption', 'columns', 'rows', 'emptyText' => 'Belum ada data.', 'title' => null, 'stackMobile' => false, 'showControls' => true, 'footerRows' => [], 'tableClass' => '', 'paginator' => null])

<section class="member-table-card member-reveal" data-reveal @if ($showControls && $paginator === null) data-member-table="{{ $tableId }}" @endif>
    @if ($title !== null)
        <h2 class="member-table-card__title">{{ $title }}</h2>
    @endif
    @if ($showControls)
        @if ($paginator !== null)
            <x-anggota.server-table-controls :table-id="$tableId" :paginator="$paginator" position="top" />
        @else
            <x-anggota.table-controls :table-id="$tableId" :total="count($rows)" position="top" />
        @endif
    @endif
    <div class="member-table-wrap{{ $stackMobile ? ' member-form-table-wrap' : '' }}">
        <table @class(['member-table', 'member-table--stack-mobile' => $stackMobile, $tableClass])>
            <caption class="member-sr-only">{{ $caption }}</caption>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="member-table__{{ $column['align'] ?? 'left' }}">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody data-table-body>
                @forelse ($rows as $row)
                    <tr data-table-row>
                        @foreach ($columns as $column)
                            @php
                                $key = $column['key'];
                                $align = $column['align'] ?? 'left';
                            @endphp
                            <td class="member-table__{{ $align }}" data-column="{{ $key }}" @if ($stackMobile) data-label="{{ $column['label'] }}" @endif>
                                @if ($key === 'action')
                                    @if (! empty($row['action_url']))
                                        <a
                                            @class(['member-table-action', $column['action_class'] ?? ''])
                                            href="{{ $row['action_url'] }}"
                                            aria-label="{{ $row['action_aria_label'] }}"
                                            @if (! empty($row['action_target'])) target="{{ $row['action_target'] }}" rel="{{ $row['action_rel'] ?? 'noopener' }}" @endif
                                        >
                                            @if (($column['action_icon'] ?? null) === 'pdf')
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 3.75h7l4 4v12.5H6.5a2 2 0 0 1-2-2V5.75a2 2 0 0 1 2-2Zm7 0v4h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 15.5h1.7a1.2 1.2 0 0 0 0-2.4H7.5v4m5-4v4h1a2 2 0 0 0 0-4h-1Zm4 4v-4h2.5m-2.5 2h2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.8" cy="10.8" r="6.3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m15.5 15.5 4.2 4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                            @endif
                                            <span>{{ $column['action_label'] ?? 'Lihat detail' }}</span>
                                        </a>
                                    @endif
                                @elseif (($column['format'] ?? null) === 'currency')
                                    @if (is_numeric($row[$key] ?? null))
                                        <span class="member-table__amount">Rp {{ number_format((float) $row[$key], 0, ',', '.') }}</span>
                                    @elseif (($row[$key] ?? null) === '-')
                                        -
                                    @endif
                                @elseif (($column['format'] ?? null) === 'badge')
                                    <x-anggota.badge>{{ $row[$key] }}</x-anggota.badge>
                                @elseif (($column['format'] ?? null) === 'date')
                                    {{ \Illuminate\Support\Carbon::parse($row[$key])->locale('id')->translatedFormat('j F Y') }}
                                @elseif ($key === 'nip')
                                    <x-anggota.masked-id :value="$row[$key]" />
                                @else
                                    {{ $row[$key] }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr data-table-empty>
                        <td colspan="{{ count($columns) }}">{{ $emptyText }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($footerRows !== [])
                <tfoot>
                    @foreach ($footerRows as $footerRow)
                        <tr class="{{ $footerRow['class'] ?? '' }}">
                            @foreach ($columns as $columnIndex => $column)
                                @php
                                    $key = $column['key'];
                                    $labelSpan = $footerRow['label_span'] ?? 1;
                                @endphp
                                @if ($columnIndex === 0)
                                    <th scope="row" colspan="{{ $labelSpan }}" class="member-table__{{ $column['align'] ?? 'left' }}">
                                        {{ $footerRow['label'] ?? $footerRow[$key] ?? '' }}
                                    </th>
                                @elseif ($columnIndex >= $labelSpan)
                                    <td class="member-table__{{ $column['align'] ?? 'left' }}">
                                        @if (($column['format'] ?? null) === 'currency' && is_numeric($footerRow[$key] ?? null))
                                            <span class="member-table__amount">Rp {{ number_format((float) $footerRow[$key], 0, ',', '.') }}</span>
                                        @else
                                            {{ $footerRow[$key] ?? '' }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>
    @if ($showControls)
        @if ($paginator !== null)
            <x-anggota.server-table-controls :table-id="$tableId" :paginator="$paginator" position="bottom" />
        @else
            <x-anggota.table-controls :table-id="$tableId" :total="count($rows)" position="bottom" />
        @endif
    @endif
</section>
