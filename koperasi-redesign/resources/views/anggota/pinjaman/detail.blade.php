@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.pinjaman-styles')
    @php
        $rupiah = fn ($value) => 'Rp '.number_format($value, 0, ',', '.');
        $summaryRows = collect([
            'No. pengajuan' => $loan['application_number'],
            'Jenis pinjaman' => $loan['loan_type'],
            'Jumlah pinjaman' => $rupiah($loan['total']),
            'Tenor' => $loan['term'].' bulan',
            'Bunga per bulan' => rtrim(rtrim($loan['interest_rate'], '0'), '.').'%',
            'Metode bayar' => $loan['payment_method'],
            'Status' => $loan['status'],
            'Tanggal pengajuan' => \Illuminate\Support\Carbon::parse($loan['date'])->locale('id')->translatedFormat('j F Y'),
            'Tanggal disetujui' => $loan['approved_date'],
            'Total sudah dibayar' => $rupiah($loan['paid_total']),
            'Sisa pokok' => $rupiah($loan['remaining']),
            'Sisa angsuran' => $loan['remaining_installments'].' angsuran',
            'Jumlah tunggakan' => $loan['overdue_count'].' angsuran',
        ])->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all();
        $summaryColumns = [['key' => 'label', 'label' => 'Informasi'], ['key' => 'value', 'label' => 'Rincian']];
        $scheduleColumns = [
            ['key' => 'number', 'label' => 'Ke'],
            ['key' => 'due_date', 'label' => 'Jatuh tempo'],
            ['key' => 'principal', 'label' => 'Pokok', 'format' => 'currency', 'align' => 'right'],
            ['key' => 'interest', 'label' => 'Bunga', 'format' => 'currency', 'align' => 'right'],
            ['key' => 'total', 'label' => 'Total', 'format' => 'currency', 'align' => 'right'],
            ['key' => 'status', 'label' => 'Status', 'format' => 'badge'],
            ['key' => 'paid_date', 'label' => 'Tanggal bayar'],
        ];
    @endphp
    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pinjaman" />
        <div class="member-main">
            <x-anggota.page-header eyebrow="Pinjaman" title="Detail" accent="pinjaman" :subtitle="$loan['application_number']">
                <x-slot:actions>
                    <x-anggota.back-button :href="route('anggota.pinjaman.riwayat')" />
                </x-slot:actions>
            </x-anggota.page-header>
            <x-anggota.data-table table-id="loan-summary" caption="Ringkasan pinjaman" title="Ringkasan pinjaman" :columns="$summaryColumns" :rows="$summaryRows" :show-controls="false" />
            @if ($loan['needs_akad'] || $loan['akad_available'])
                <section class="loan-panel">
                    <h2 class="loan-panel__title">Dokumen akad murabahah</h2>
                    @if ($loan['akad_available'])
                        <a class="member-table-action" href="{{ route('anggota.pinjaman.akad', ['id' => $loan['id']]) }}">Unduh dokumen akad</a>
                    @else
                        <x-anggota.info-box>Dokumen akad belum tersedia.</x-anggota.info-box>
                    @endif
                </section>
            @endif
            <x-anggota.data-table table-id="loan-installments" caption="Jadwal angsuran pinjaman" title="Jadwal angsuran" :columns="$scheduleColumns" :rows="$loan['schedule']" :show-controls="false" empty-text="Belum ada jadwal angsuran." stack-mobile />
        </div>
    </div>
@endsection
