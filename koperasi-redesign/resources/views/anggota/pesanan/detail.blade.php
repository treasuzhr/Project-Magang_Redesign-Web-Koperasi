@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')
    @include('anggota.partials.pesanan-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pesanan" />

        <div class="member-main member-order-detail">
            <x-anggota.back-button :href="route('anggota.pesanan.index')" />

            <x-anggota.page-header
                eyebrow="PESANAN"
                title="Detail"
                accent="order"
                :subtitle="'Order tanggal '.$order['date'].'.'"
            />

            <x-anggota.info-box>Status: {{ $order['status'] }}</x-anggota.info-box>

            @if ($order['items'] !== [])
                <section class="member-table-card member-reveal" data-reveal aria-labelledby="order-items-title">
                    <h2 id="order-items-title" class="member-sr-only">Detail barang order</h2>
                    <div class="member-table-wrap">
                        <table class="member-order-items">
                            <caption class="member-sr-only">Barang pada order tanggal {{ $order['date'] }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Nama produk</th>
                                    <th scope="col" class="member-table__right">Harga</th>
                                    <th scope="col" class="member-table__center">Jumlah</th>
                                    <th scope="col" class="member-table__right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order['items'] as $item)
                                    <tr>
                                        <td class="member-order-items__product" data-label="Nama produk">
                                            <div class="member-order-items__thumbnail" aria-hidden="true">
                                                @php
                                                    // TODO: ganti dengan foto produk asli
                                                @endphp
                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                                    <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                                                    <circle cx="9" cy="9" r="1.5" fill="currentColor"/>
                                                    <path d="m5 17 4.5-4.5 3 3 2-2L19 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <span>{{ $item['name'] }}</span>
                                        </td>
                                        <td class="member-table__right" data-label="Harga">
                                            Rp {{ number_format($item['price'], 0, ',', '.') }}
                                        </td>
                                        <td class="member-table__center" data-label="Jumlah">{{ $item['quantity'] }}</td>
                                        <td class="member-table__right member-order-items__total" data-label="Total">
                                            Rp {{ number_format($item['total'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" scope="row">Total pesanan</th>
                                    <td class="member-table__right">Rp {{ number_format($order['total'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @else
                <section class="member-table-card member-order-empty member-reveal" data-reveal>
                    <p>Detail belum tersedia.</p>
                </section>
            @endif
        </div>
    </div>
@endsection
