@extends('layouts.anggota')

@section('member-content')
    @include('anggota.partials.avatar-styles')

    @php
        $initials = collect(preg_split('/\s+/', trim($member['name'])))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
        $avatarError = $errors->first('avatar');
    @endphp

    <div class="member-shell page-width">
        <x-anggota.sidebar :member="$member" active="none" />

        <div class="member-main member-avatar-page">
            <x-anggota.page-header
                eyebrow="AKUN"
                title="Ubah"
                accent="avatar"
                subtitle="Ganti foto profil akun Anda."
            />

            @if (session('status'))
                <x-anggota.info-box>{{ session('status') }}</x-anggota.info-box>
            @endif

            <div class="member-avatar-page__cards">
                <section class="member-avatar-card member-avatar-card__identity member-reveal" data-reveal aria-labelledby="avatar-current-title">
                    <h2 class="member-sr-only" id="avatar-current-title">Foto profil</h2>
                    <div class="member-avatar-card__photo">
                        @if ($member['avatar_url'])
                            <img src="{{ $member['avatar_url'] }}" alt="Foto profil {{ $member['name'] }}">
                        @else
                            <span aria-label="Inisial {{ $member['name'] }}">{{ $initials }}</span>
                        @endif
                    </div>
                    <p class="member-avatar-card__name">{{ $member['name'] }}</p>
                    <p class="member-avatar-card__id"><x-anggota.masked-id :value="$member['nip']" /></p>
                    <p class="member-avatar-card__state">{{ $member['avatar_url'] ? 'Foto profil saat ini' : 'Belum ada foto profil' }}</p>

                    @if ($member['avatar_url'])
                        <form class="member-avatar-delete" method="POST" action="{{ route('anggota.avatar.delete') }}" data-avatar-delete-form>
                            @csrf
                            @method('DELETE')
                            <button class="member-avatar-delete__trigger" type="button" data-avatar-delete-trigger>
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                                Hapus foto
                            </button>
                            <div class="member-avatar-delete__confirm" data-avatar-delete-confirm hidden>
                                <span>Hapus foto profil?</span>
                                <button type="submit">Ya, hapus</button>
                                <button type="button" data-avatar-delete-cancel>Batal</button>
                            </div>
                        </form>
                    @endif
                </section>

                <form
                    class="member-avatar-card member-avatar-upload member-reveal"
                    data-reveal
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('anggota.avatar.upload') }}"
                    data-avatar-upload-form
                    data-max-bytes="{{ $avatarConfig['max_bytes'] }}"
                    data-max-label="{{ $avatarConfig['max_size_label'] }}"
                    data-accept="{{ $avatarConfig['accept'] }}"
                >
                    @csrf
                    <h2 class="member-avatar-upload__title">Unggah foto baru</h2>

                    <label class="member-avatar-dropzone" data-avatar-dropzone @if ($avatarError) aria-describedby="avatar-error" @endif>
                        <input
                            class="member-avatar-dropzone__input"
                            type="file"
                            name="avatar"
                            accept="{{ $avatarConfig['accept'] }}"
                            aria-label="Pilih foto profil"
                            aria-invalid="{{ $avatarError ? 'true' : 'false' }}"
                            @if ($avatarError) aria-describedby="avatar-error" @endif
                            data-avatar-input
                        >
                        <svg class="member-avatar-dropzone__icon" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
                        <span class="member-avatar-dropzone__heading">Seret foto ke sini atau pilih file</span>
                        <span class="member-avatar-dropzone__help">{{ implode(', ', array_slice($avatarConfig['formats'], 0, -1)) }}, atau {{ end($avatarConfig['formats']) }}. Maksimal {{ $avatarConfig['max_size_label'] }}.</span>
                    </label>

                    <div class="member-avatar-file" data-avatar-file hidden>
                        <span class="member-avatar-file__thumbnail" data-avatar-thumbnail></span>
                        <span class="member-avatar-file__details">
                            <span class="member-avatar-file__name" data-avatar-filename></span>
                            <span class="member-avatar-file__size" data-avatar-filesize></span>
                        </span>
                        <span class="member-avatar-file__actions">
                            <button class="member-avatar-file__change" type="button" data-avatar-change>Ganti foto</button>
                            <button class="member-avatar-file__clear" type="button" aria-label="Batalkan pilihan foto" data-avatar-clear>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                            </button>
                        </span>
                    </div>

                    <p class="member-avatar-error" id="avatar-error" role="alert" data-avatar-error @if (!$avatarError) hidden @endif>{{ $avatarError }}</p>

                    <div class="member-avatar-preview" data-avatar-preview hidden aria-live="polite">
                        <h3 class="member-avatar-preview__title">Pratinjau</h3>
                        <div class="member-avatar-preview__sizes">
                            <div class="member-avatar-preview__item">
                                <span class="member-avatar-preview__circle" data-avatar-preview-desktop></span>
                                <span>Di sidebar</span>
                            </div>
                            <div class="member-avatar-preview__item">
                                <span class="member-avatar-preview__circle member-avatar-preview__circle--mobile" data-avatar-preview-mobile></span>
                                <span>Di ponsel</span>
                            </div>
                        </div>
                        <p class="member-avatar-preview__note">Foto akan dipotong bulat di bagian tengah.</p>
                    </div>

                    <ul class="member-avatar-notes" aria-label="Catatan foto profil">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg>Pakai foto persegi agar tidak terpotong.</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg>Pastikan wajah terlihat jelas.</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg>Foto lama akan diganti.</li>
                    </ul>

                    <div class="member-avatar-actions">
                        <a class="member-avatar-actions__cancel" href="{{ route('anggota.dashboard') }}">Batal</a>
                        <x-anggota.primary-button type="submit" aria-disabled="true" disabled data-avatar-submit>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
                            <span data-avatar-submit-label>Unggah foto</span>
                        </x-anggota.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
