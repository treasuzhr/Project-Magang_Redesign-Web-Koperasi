@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="simpanan" />

        <div class="member-main">
            <x-anggota.page-header
                eyebrow="Simpanan"
                :title="$headingPrefix"
                :accent="$headingAccent"
                :subtitle="$subtitle"
            />

            <x-anggota.tabs :tabs="$tabs" />

            <x-anggota.data-table
                table-id="savings-table"
                :caption="'Riwayat simpanan '.$jenisLabel"
                :columns="$columns"
                :rows="$rows"
                :paginator="$paginator"
            />
        </div>
    </div>
@endsection
