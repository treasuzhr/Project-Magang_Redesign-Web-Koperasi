@props(['member', 'active' => null])

@php
    $active ??= request()->routeIs('anggota.dashboard')
        ? 'dashboard'
        : (request()->routeIs('anggota.pesanan.*')
            ? 'pesanan'
            : (request()->routeIs('anggota.pinjaman.*') ? 'pinjaman' : 'simpanan'));
@endphp

@once
    <style>
        .member-sidebar {
            position: sticky;
            top: 100px;
            display: grid;
            gap: 22px;
            align-self: start;
        }

        .member-sidebar .member-card {
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 14px 28px rgba(21, 36, 28, .06);
        }

        .member-profile {
            padding: 28px 22px 18px;
            text-align: center;
        }

        .member-avatar {
            display: grid;
            overflow: hidden;
            place-items: center;
            width: 88px;
            height: 88px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #0e3b2b;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .member-avatar__image {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .member-profile h2 {
            margin: 0 0 6px;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(1.2rem, 2vw, 1.6rem);
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -.04em;
        }

        .member-profile__nip {
            margin: 0;
            color: #5b6d62;
            font-size: .82rem;
            letter-spacing: .02em;
        }

        .member-divider {
            height: 1px;
            margin: 18px 0;
            background: rgba(21, 36, 28, .09);
        }

        .member-label {
            display: block;
            color: #5b6d62;
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .member-value {
            display: block;
            margin-top: 8px;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: .96rem;
            font-weight: 700;
        }

        .member-profile__links {
            display: grid;
            gap: 10px;
            margin-top: 10px;
            text-align: left;
        }

        .member-profile__links a {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #15241c;
            text-decoration: none;
            font-size: .88rem;
            font-weight: 600;
        }

        .member-profile__links a svg,
        .member-nav__item svg,
        .member-nav__logout svg {
            display: block;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            color: currentColor;
            stroke: currentColor;
        }

        .member-profile__links a:hover,
        .member-profile__links a:focus-visible {
            color: #0e3b2b;
        }

        .member-profile__links a.is-current {
            color: #527d28;
        }

        .member-nav {
            overflow: hidden;
            padding: 18px 14px;
        }

        .member-nav__list {
            display: grid;
            gap: 6px;
        }

        .member-nav__item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            color: #15241c;
            text-decoration: none;
            font-weight: 600;
            transition: transform .2s ease;
        }

        .member-nav__item:not(.is-active):hover,
        .member-nav__item:not(.is-active):focus-visible {
            background: #edf7dd;
            transform: translateX(2px);
        }

        .member-nav__item.is-active {
            background: #0e3b2b;
            color: #fff;
        }

        .member-nav__divider {
            height: 1px;
            margin: 12px 8px 10px;
            background: rgba(21, 36, 28, .08);
        }

        .member-nav__logout {
            margin-top: 10px;
        }

        .member-nav__logout button {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 12px;
            border: 0;
            background: transparent;
            padding: 12px 14px;
            border-radius: 16px;
            color: #15241c;
            font: inherit;
            font-weight: 600;
            line-height: 1.3;
            cursor: pointer;
            transition: transform .2s ease;
        }

        .member-nav__logout button:hover,
        .member-nav__logout button:focus-visible {
            background: #edf7dd;
            transform: translateX(2px);
        }

        @media (max-width: 1023px) {
            .member-sidebar {
                position: static;
            }

            .member-profile {
                display: flex;
                align-items: center;
                gap: 16px;
                text-align: left;
                padding: 18px 20px;
            }

            .member-avatar {
                width: 56px;
                height: 56px;
                margin: 0;
                font-size: 1.2rem;
            }

            .member-profile__details {
                flex: 1;
            }

            .member-divider,
            .member-label,
            .member-value {
                display: none;
            }

            .member-profile__links {
                display: flex;
                flex-wrap: wrap;
                gap: 4px 12px;
                margin-top: 6px;
            }

            .member-profile__links a {
                min-height: 44px;
            }

            .member-nav {
                padding: 12px 10px 14px;
            }

            .member-nav__list {
                display: flex;
                overflow-x: auto;
                gap: 8px;
                padding-bottom: 4px;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .member-nav__list::-webkit-scrollbar {
                display: none;
            }

            .member-nav__item,
            .member-nav__logout button {
                white-space: nowrap;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .member-nav__item,
            .member-nav__logout button,
            .member-profile__links a {
                transition: none;
            }
        }
    </style>
@endonce

<aside class="member-sidebar">
    <div class="member-card member-profile">
        <div class="member-profile__details">
            <div class="member-avatar">
                @if (!empty($member['avatar_url']))
                    <img class="member-avatar__image" src="{{ $member['avatar_url'] }}" alt="Foto profil {{ $member['name'] }}">
                @else
                    <span aria-label="Inisial {{ $member['name'] }}">{{ collect(preg_split('/\s+/', trim($member['name'])))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}</span>
                @endif
            </div>
            <h2>{{ $member['name'] }}</h2>
            <p class="member-profile__nip"><x-anggota.masked-id :value="$member['nip']" /></p>
            <div class="member-divider"></div>
            <span class="member-label">Jabatan</span>
            <span class="member-value">{{ $member['jabatan'] }}</span>
            <div class="member-divider"></div>
            <div class="member-profile__links">
                <a class="{{ request()->routeIs('anggota.password.*') ? 'is-current' : '' }}" href="{{ route('anggota.password.edit') }}" @if (request()->routeIs('anggota.password.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7.5a5 5 0 0 1 10 0V10m-11.25 0h12.5A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21h-12.5A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10Zm6.25 4v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Ubah password
                </a>
                <a class="{{ request()->routeIs('anggota.avatar.*') ? 'is-current' : '' }}" href="{{ route('anggota.avatar.edit') }}" @if (request()->routeIs('anggota.avatar.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0m3-8.5 1.5-1.5 2.5 2.5-1.5 1.5m-2.5-2.5-4 4v2h2l4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Ubah avatar
                </a>
            </div>
        </div>
    </div>

    <nav class="member-card member-nav" aria-label="Menu anggota">
        <div class="member-nav__list">
            <a class="member-nav__item {{ $active === 'dashboard' ? 'is-active' : '' }}" href="{{ route('anggota.dashboard') }}" @if ($active === 'dashboard') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12.5 12 4l8 8.5V20a1 1 0 0 1-1 1h-4v-7H9v7H5a1 1 0 0 1-1-1v-7.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Dashboard
            </a>
            <a class="member-nav__item {{ $active === 'simpanan' ? 'is-active' : '' }}" href="{{ route('anggota.simpanan.index') }}" @if ($active === 'simpanan') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.25A2.25 2.25 0 0 1 6.25 6h11.5A2.25 2.25 0 0 1 20 8.25v9.5A2.25 2.25 0 0 1 17.75 20h-11.5A2.25 2.25 0 0 1 4 17.75v-9.5Zm0 2.75h16m-4.5 4h1.75" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Simpanan
            </a>
            <a class="member-nav__item {{ $active === 'pinjaman' ? 'is-active' : '' }}" href="{{ route('anggota.pinjaman.index') }}" @if ($active === 'pinjaman') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="6" width="17" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7 10h10m-10 4h4m-2-2 2-2m-2 2 2 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Pinjaman
            </a>
            <a class="member-nav__item {{ $active === 'shu' ? 'is-active' : '' }}" href="{{ route('anggota.shu.index') }}" @if ($active === 'shu') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5h16M6.5 17V11m5.5 6V6.5m5.5 10.5V8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m5 8 6-4 5 2 3-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                SHU
            </a>
            <a class="member-nav__item {{ $active === 'pesanan' ? 'is-active' : '' }}" href="{{ route('anggota.pesanan.index') }}" @if ($active === 'pesanan') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9.5h16l-1.5 11h-13L4 9.5Zm0 0 2-5h12l2 5M9 9.5V8a3 3 0 0 1 6 0v1.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Pesanan
            </a>
            <a class="member-nav__item {{ $active === 'dokumen' ? 'is-active' : '' }}" href="{{ route('anggota.dokumen.index') }}" @if ($active === 'dokumen') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 3.75h7l4 4v12.5H6.5a2 2 0 0 1-2-2V5.75a2 2 0 0 1 2-2Zm7 0v4h4m-9 4h6m-6 3.5h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Dokumen
            </a>
        </div>
        <div class="member-nav__divider"></div>
        <form class="member-nav__logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 7V5.75A1.75 1.75 0 0 0 12.25 4h-6.5A1.75 1.75 0 0 0 4 5.75v12.5A1.75 1.75 0 0 0 5.75 20h6.5A1.75 1.75 0 0 0 14 18.25V17m-1-5H22m0 0-3-3m3 3-3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Keluar
            </button>
        </form>
    </nav>
</aside>
