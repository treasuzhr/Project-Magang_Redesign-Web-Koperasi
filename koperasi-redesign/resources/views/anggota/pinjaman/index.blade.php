@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.pinjaman-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pinjaman" />

        <div class="member-main">
            <x-anggota.page-header eyebrow="Pinjaman" title="Halaman" accent="Pinjaman" />

            <div class="loan-action-grid">
                <a class="loan-action-card loan-action-card--primary" href="{{ route('anggota.pinjaman.ajukan') }}" data-reveal>
                    <div>
                        <span class="loan-action-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14m7-7H5"/></svg>
                        </span>
                        <h2 class="loan-action-card__title">Ajukan Pinjaman</h2>
                        <p class="loan-action-card__description">Ajukan pinjaman koperasi dengan mudah.</p>
                    </div>
                    <span class="loan-action-card__link">Ajukan sekarang</span>
                </a>

                <a class="loan-action-card loan-action-card--secondary" href="{{ route('anggota.pinjaman.riwayat') }}" data-reveal>
                    <div>
                        <span class="loan-action-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <h2 class="loan-action-card__title">Riwayat Pinjaman</h2>
                        <p class="loan-action-card__description">Lihat status dan riwayat pinjaman Anda.</p>
                    </div>
                    <span class="loan-action-card__link">Lihat riwayat</span>
                </a>
            </div>

            <section class="loan-panel loan-panel--active member-reveal" data-reveal>
                <h2 class="loan-panel__title">Pinjaman aktif</h2>
                @if ($activeLoans === [])
                    <x-anggota.info-box>Anda belum memiliki pinjaman aktif.</x-anggota.info-box>
                @else
                    <x-anggota.data-table table-id="active-loans-table" caption="Pinjaman aktif anggota" :columns="$columns" :rows="$activeLoans" :paginator="$paginator" stack-mobile />
                @endif
            </section>
        </div>
    </div>
@endsection
