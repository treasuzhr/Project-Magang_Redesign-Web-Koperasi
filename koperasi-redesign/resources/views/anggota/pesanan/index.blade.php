@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')
    @include('anggota.partials.pesanan-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pesanan" />

        <div class="member-main member-orders">
            <x-anggota.page-header
                eyebrow="PESANAN"
                title="Data riwayat"
                accent="order"
                subtitle="Daftar belanja Anda di Def.MART."
            />

            <x-anggota.data-table
                table-id="orders-table"
                caption="Data riwayat order"
                :columns="$columns"
                :rows="$rows"
                empty-text="Belum ada pesanan."
                stack-mobile
                table-class="member-orders-table"
            />
        </div>
    </div>
@endsection
