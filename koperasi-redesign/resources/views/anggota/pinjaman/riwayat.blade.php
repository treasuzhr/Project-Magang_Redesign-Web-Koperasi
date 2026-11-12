@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.pinjaman-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pinjaman" />

        <div class="member-main">
            <x-anggota.page-header
                eyebrow="Pinjaman"
                title="Riwayat"
                accent="pinjaman"
                subtitle="Status dan riwayat pinjaman Anda."
            >
                <x-slot:actions>
                    <x-anggota.back-button :href="route('anggota.pinjaman.index')" />
                </x-slot:actions>
            </x-anggota.page-header>

            @if (session('status'))
                <x-anggota.info-box>{{ session('status') }}</x-anggota.info-box>
            @endif

            <x-anggota.data-table
                table-id="loan-history-table"
                caption="Riwayat pinjaman anggota"
                title="Riwayat Pinjaman"
                :columns="$columns"
                :rows="$rows"
                :paginator="$paginator"
                empty-text="Belum ada riwayat pinjaman."
                stack-mobile
            />
        </div>
    </div>
@endsection
