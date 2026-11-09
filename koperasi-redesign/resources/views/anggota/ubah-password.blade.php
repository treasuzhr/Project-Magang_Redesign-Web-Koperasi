@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.password-styles')

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="none" />

        <div class="member-main member-password">
            <x-anggota.page-header
                eyebrow="AKUN"
                title="Ubah"
                accent="password"
                subtitle="Ganti password akun Anda."
            />

            @if (session('status'))
                <x-anggota.info-box>{{ session('status') }}</x-anggota.info-box>
            @endif

            @if ($errors->count() > 1)
                <x-anggota.info-box class="member-password__summary" role="alert">
                    Periksa kembali isian yang ditandai di bawah ini.
                </x-anggota.info-box>
            @endif

            <form
                class="member-password__form"
                method="POST"
                action="{{ route('anggota.password.update') }}"
                novalidate
                data-member-password-form
            >
                @csrf
                <section class="loan-panel loan-form-card member-password__card member-reveal" data-reveal aria-labelledby="password-form-title">
                    <h2 class="loan-panel__title member-password__title" id="password-form-title">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7.5a5 5 0 0 1 10 0V10m-11.25 0h12.5A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21h-12.5A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10Zm6.25 4v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Ubah password
                    </h2>

                    <div class="loan-form-card__fields">
                        <x-anggota.form-field
                            id="member-password"
                            name="password"
                            label="Password baru"
                            type="password"
                            placeholder="Masukkan password baru"
                            help="Minimal 4 karakter."
                            :error="$errors->has('password') ? $errors->first('password') : null"
                            autocomplete="new-password"
                            data-member-password-input
                        >
                            <x-slot:prefixContent>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10V7.5a5 5 0 0 1 10 0V10m-11.25 0h12.5A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21h-12.5A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10Zm6.25 4v3"/></svg>
                            </x-slot:prefixContent>
                            <x-slot:suffixControl>
                                <button class="member-password__toggle" type="button" aria-label="Tampilkan password" aria-pressed="false" data-password-toggle="member-password">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
                            </x-slot:suffixControl>
                        </x-anggota.form-field>

                        <x-anggota.form-field
                            id="member-password-confirmation"
                            name="password_confirmation"
                            label="Ulangi password baru"
                            type="password"
                            placeholder="Ulangi password baru"
                            :error="$errors->has('password_confirmation') ? $errors->first('password_confirmation') : null"
                            autocomplete="new-password"
                            data-member-password-input
                        >
                            <x-slot:prefixContent>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10V7.5a5 5 0 0 1 10 0V10m-11.25 0h12.5A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21h-12.5A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10Zm6.25 4v3"/></svg>
                            </x-slot:prefixContent>
                            <x-slot:suffixControl>
                                <button class="member-password__toggle" type="button" aria-label="Tampilkan password" aria-pressed="false" data-password-toggle="member-password-confirmation">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
                            </x-slot:suffixControl>
                        </x-anggota.form-field>
                    </div>

                    <div class="member-password__actions">
                        <a class="member-password__cancel" href="{{ route('anggota.dashboard') }}">Batal</a>
                        <x-anggota.primary-button type="submit" class="member-password__submit" data-password-submit>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h12l3 3v13H4V4h1Zm3 0v6h8V4m-8 16v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span data-password-submit-label>Simpan password</span>
                        </x-anggota.primary-button>
                    </div>
                </section>
            </form>
        </div>
    </div>
@endsection
