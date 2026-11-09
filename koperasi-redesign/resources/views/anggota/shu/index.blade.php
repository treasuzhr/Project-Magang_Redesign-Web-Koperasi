@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')
    @include('anggota.partials.pinjaman-styles')
    @include('anggota.partials.shu-styles')

    <div class="member-shell page-width shu-page">
        <x-anggota.sidebar :member="$member" active="shu" />

        <div class="member-main">
            <x-anggota.page-header
                eyebrow="SHU"
                title="Data"
                accent="SHU"
                subtitle="Sisa Hasil Usaha Anda per tahun."
            />

            <section class="shu-summary member-reveal" data-reveal aria-label="Ringkasan SHU">
                <div class="shu-summary__content">
                    <span class="shu-summary__label">SHU tahun {{ $latestShu['year'] }}</span>
                    <strong
                        class="shu-summary__amount"
                        data-shu-count-up="{{ $latestShu['amount'] }}"
                    >Rp {{ number_format($latestShu['amount'], 0, ',', '.') }}</strong>
                    <a class="shu-summary__link" href="{{ route('anggota.shu.detail', ['tahun' => $latestShu['year']]) }}">Lihat detail</a>
                </div>
                <x-anggota.shu-chart :data="$chartData" :latest-year="$latestShu['year']" />
            </section>

            <div class="shu-data-table">
                <x-anggota.data-table
                    table-id="shu-table"
                    caption="Data SHU anggota per tahun"
                    title="Data SHU"
                    :columns="$columns"
                    :rows="$rows"
                    stack-mobile
                />
            </div>
        </div>
    </div>
@endsection
