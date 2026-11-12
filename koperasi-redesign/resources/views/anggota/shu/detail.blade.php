@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')
    @include('anggota.partials.shu-styles')

    <div class="member-shell page-width shu-page">
        <x-anggota.sidebar :member="$member" active="shu" />

        <div class="member-main">
            <x-anggota.page-header
                eyebrow="SHU"
                title="Detail"
                accent="SHU"
                suffix="tahun"
                :accent-suffix="$tahun"
            >
                <x-slot:actions>
                    <x-anggota.back-button :href="route('anggota.shu.index')" />
                </x-slot:actions>
            </x-anggota.page-header>

            <x-anggota.data-table
                table-id="shu-detail-table"
                caption="Detail data SHU tahun {{ $tahun }}"
                title="Detail data SHU"
                :columns="$columns"
                :rows="$rows"
                empty-text="Belum ada data."
                :footer-rows="$totalRows"
                table-class="shu-detail-table"
                :show-controls="false"
            />
        </div>
    </div>
@endsection
