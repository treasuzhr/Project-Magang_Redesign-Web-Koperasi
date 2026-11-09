@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.pinjaman-styles')
    @include('anggota.partials.dokumen-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="dokumen" />

        <div class="member-main member-documents">
            <x-anggota.page-header
                eyebrow="DOKUMEN"
                title="Dokumen"
                accent="koperasi"
                subtitle="Unduh dokumen koperasi dalam format PDF."
            />

            @if (session('status'))
                <x-anggota.info-box class="member-documents__notice">{{ session('status') }}</x-anggota.info-box>
            @endif

            <x-anggota.data-table
                table-id="documents-table"
                caption="Daftar dokumen koperasi"
                :columns="$columns"
                :rows="$rows"
                empty-text="Belum ada dokumen."
                stack-mobile
                table-class="member-documents-table"
            />
        </div>
    </div>
@endsection
