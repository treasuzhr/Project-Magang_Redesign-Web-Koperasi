@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.pinjaman-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="pinjaman" />

        <div class="member-main">
            <x-anggota.page-header
                eyebrow="Pinjaman"
                title="Pengajuan"
                accent="pinjaman"
                subtitle="Isi form, lihat estimasi angsuran, lalu ajukan."
            >
                <x-slot:actions>
                    <x-anggota.back-button :href="route('anggota.pinjaman.index')" />
                </x-slot:actions>
            </x-anggota.page-header>

            @if (session('status'))
                <x-anggota.info-box>{{ session('status') }}</x-anggota.info-box>
            @endif

            @if ($errors->any())
                <x-anggota.info-box class="loan-form-summary" role="alert">
                    Periksa kembali isian yang ditandai di bawah ini.
                </x-anggota.info-box>
            @endif

            <form
                class="loan-form-grid"
                method="POST"
                action="{{ route('anggota.pinjaman.submit') }}"
                novalidate
                data-loan-form
                data-monthly-interest-rates="{{ json_encode($monthlyInterestRates) }}"
            >
                @csrf
                <section class="loan-panel loan-form-card member-reveal" data-reveal aria-labelledby="loan-form-title">
                    <h2 class="loan-panel__title" id="loan-form-title">Form pinjaman</h2>
                    <div class="loan-form-card__fields">
                        <x-anggota.form-field
                            id="loan-type"
                            name="loan_type"
                            label="Jenis pinjaman"
                            type="select"
                            placeholder="-- Pilih Jenis Pinjaman --"
                            :value="old('loan_type')"
                            :options="$loanTypes"
                            :error="$errors->has('loan_type') ? $errors->first('loan_type') : null"
                        />

                        <x-anggota.form-field
                            id="loan-total"
                            name="loan_total"
                            label="Total pinjaman"
                            placeholder="Contoh: 10000000"
                            :value="old('loan_total')"
                            inputmode="numeric"
                            prefix="Rp"
                            :help="$formattedLoanTotal"
                            :error="$errors->has('loan_total') ? $errors->first('loan_total') : null"
                            dynamic-help
                            data-loan-total
                        />

                        <x-anggota.form-field
                            id="loan-term"
                            name="loan_term"
                            label="Tenor bulan"
                            placeholder="Contoh: 12"
                            :value="old('loan_term')"
                            inputmode="numeric"
                            suffix="bulan"
                            :error="$errors->has('loan_term') ? $errors->first('loan_term') : null"
                            data-loan-term
                        />

                        <x-anggota.form-field
                            id="payment-method"
                            name="payment_method"
                            label="Metode pembayaran"
                            type="select"
                            placeholder="-- Pilih Metode Pembayaran --"
                            :value="old('payment_method')"
                            :options="$paymentMethods"
                            :error="$errors->has('payment_method') ? $errors->first('payment_method') : null"
                        />
                    </div>
                </section>

                <section class="loan-simulation-card member-reveal" data-reveal aria-labelledby="loan-simulation-title">
                    <h2 class="loan-simulation-card__title" id="loan-simulation-title">Simulasi angsuran</h2>
                    <x-anggota.simulation-row label="Pokok / bulan" :value="$principalEstimate" />
                    <x-anggota.simulation-row label="Bunga / bulan" :value="$interestEstimate" />
                    <x-anggota.simulation-row label="Total / bulan" :value="$monthlyTotalEstimate" emphasis />
                    <p class="loan-simulation-card__note">Simulasi bersifat estimasi, hasil final mengikuti persetujuan koperasi.</p>
                </section>

                <div class="loan-form-submit">
                    <x-anggota.primary-button type="submit" data-loan-submit>
                        <span data-loan-submit-label>Ajukan pinjaman</span>
                    </x-anggota.primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection
