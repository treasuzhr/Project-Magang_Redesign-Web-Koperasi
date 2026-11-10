@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.simpanan-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="simpanan" />

        <div class="member-main">
            <x-anggota.page-header
                :eyebrow="'Simpanan '.$jenisLabel"
                title="Detail tahun"
                :accent="$tahun"
            >
                <x-slot:actions>
                    <x-anggota.back-button :href="$backUrl" />
                </x-slot:actions>
            </x-anggota.page-header>

            <x-anggota.data-table
                table-id="savings-detail-table"
                :caption="'Detail simpanan '.$jenisLabel.' tahun '.$tahun"
                title="Detail data"
                :columns="$columns"
                :rows="$rows"
                :paginator="$paginator"
                :empty-text="$emptyText"
            />
        </div>
    </div>
@endsection
