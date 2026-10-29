@extends('layouts.anggota')

@php
    use Illuminate\Support\Str;
@endphp

@section('member-content')
    <style>
        .member-portal {
            background: #f3f6ee;
            padding: 54px 0 80px;
            overflow-x: clip;
        }

        .member-portal .page-width {
            width: min(100% - 32px, 1360px);
            margin-inline: auto;
        }

        .member-shell {
            display: grid;
            grid-template-columns: minmax(0, 290px) minmax(0, 1fr);
            gap: 32px;
            align-items: start;
        }

        .member-card {
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 14px 28px rgba(21, 36, 28, 0.06);
        }

        .member-sidebar {
            position: sticky;
            top: 98px;
            display: grid;
            gap: 22px;
        }

        .member-profile {
            padding: 28px 22px 18px;
            text-align: center;
        }

        .member-avatar {
            display: grid;
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
            background: rgba(21, 36, 28, 0.09);
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
            gap: 10px;
            color: #15241c;
            text-decoration: none;
            font-size: .88rem;
            font-weight: 600;
        }

        .member-profile__links a:hover,
        .member-profile__links a:focus-visible {
            color: #0e3b2b;
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
            transition: transform .2s ease, background-color .2s ease, color .2s ease;
        }

        .member-nav__item svg {
            width: 18px;
            height: 18px;
            color: currentColor;
        }

        .member-nav__item:hover,
        .member-nav__item:focus-visible {
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
            background: rgba(21, 36, 28, 0.08);
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
            cursor: pointer;
            transition: transform .2s ease, background-color .2s ease;
        }

        .member-nav__logout button:hover,
        .member-nav__logout button:focus-visible {
            background: #edf7dd;
            transform: translateX(2px);
        }

        .member-main {
            display: grid;
            gap: 28px;
        }

        .member-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: end;
        }

        .member-header__eyebrow {
            margin: 0 0 8px;
            color: #7fad39;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .member-header h1 {
            margin: 0;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(2.15rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -.06em;
            line-height: 1.10;
        }

        .member-header h1 span {
            color: #7fad39;
        }

        .member-header__subtitle {
            margin: 10px 0 0;
            color: #5b6d62;
            font-size: 1rem;
        }

        .member-header__actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .member-summary {
            position: relative;
            overflow: hidden;
            padding: 28px 28px 24px;
            background: linear-gradient(135deg, #0e3b2b 0%, #173d2b 100%);
            border-radius: 32px;
            box-shadow: 0 18px 36px rgba(14, 59, 43, 0.15);
        }

        .member-summary::before {
            position: absolute;
            inset: auto -15% -38% auto;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(127, 173, 57, 0.12);
            content: '';
        }

        .member-summary__inner {
            position: relative;
            z-index: 1;
        }

        .member-summary__label {
            display: inline-block;
            color: rgba(255, 255, 255, 0.74);
            font-size: .75rem;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .member-summary__amount {
            display: block;
            margin-top: 12px;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(2.4rem, 3vw, 3.4rem);
            font-weight: 700;
            letter-spacing: -.06em;
            line-height: 1.1;
        }

        .member-summary__bar {
            position: relative;
            display: flex;
            overflow: hidden;
            width: 100%;
            height: 16px;
            margin-top: 24px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        .member-summary__bar.is-visible .member-summary__segment {
            transform: scaleX(1);
            opacity: 1;
        }

        .member-summary__segment {
            display: block;
            height: 100%;
            opacity: 0;
            transform: scaleX(0);
            transform-origin: left center;
            transition: transform .7s cubic-bezier(.2,.8,.2,1), opacity .5s ease;
        }

        .member-summary__segment:first-child {
            border-radius: 999px 0 0 999px;
        }

        .member-summary__segment:last-child {
            border-radius: 0 999px 999px 0;
        }

        .member-summary__legend {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
            margin-top: 20px;
        }

        .member-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: .82rem;
        }

        .member-legend-item__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            background: var(--dot-color, #7fad39);
        }

        .member-legend-item__meta {
            display: flex;
            width: 100%;
            justify-content: space-between;
            gap: 12px;
            min-width: 0;
        }

        .member-legend-item__meta strong {
            color: #fff;
            font-weight: 700;
        }

        .member-summary__link {
            display: inline-flex;
            margin-top: 18px;
            align-items: center;
            color: rgba(255, 255, 255, 0.82);
            font-size: .84rem;
            font-weight: 600;
            text-decoration: none;
        }

        .member-summary__link:hover,
        .member-summary__link:focus-visible {
            color: #fff;
        }

        .member-stats {
            display: grid;
            gap: 18px;
        }

        .member-stats__group {
            display: grid;
            gap: 16px;
        }

        .member-stats__group-title {
            margin: 0;
            color: #15241c;
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .member-stats__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .member-stat-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 160px;
            padding: 18px;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(21, 36, 28, 0.05);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .member-stat-card:hover,
        .member-stat-card:focus-within {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(21, 36, 28, 0.08);
        }

        .member-stat-card__top {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #5b6d62;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .member-stat-card__icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #edf7dd;
            color: #0e3b2b;
        }

        .member-stat-card__icon svg {
            width: 18px;
            height: 18px;
        }

        .member-stat-card strong {
            display: block;
            margin-top: 18px;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(1.05rem, 1.3vw, 1.6rem);
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .member-breakdown {
            display: grid;
            grid-template-columns: minmax(0, 340px) minmax(0, 1fr);
            gap: 20px;
            padding: 22px 20px 18px;
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(21, 36, 28, 0.05);
        }

        .member-breakdown__donut {
            display: grid;
            place-items: center;
            min-height: 230px;
        }

        .member-donut {
            position: relative;
            width: min(100%, 250px);
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            isolation: isolate;
        }

        .member-donut svg {
            width: 100%;
            height: 100%;
            overflow: visible;
            transform: rotate(-90deg);
        }

        .member-donut__bg {
            fill: none;
            stroke: #eef3ea;
            stroke-width: 14;
        }

        .member-donut__segment {
            fill: none;
            stroke-width: 14;
            stroke-linecap: round;
            opacity: 0;
            transform-origin: center;
            transition: opacity .5s ease, stroke-dasharray .8s ease, transform .8s ease;
        }

        .member-donut__segment.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        .member-donut__center {
            position: absolute;
            inset: 50% auto auto 50%;
            display: grid;
            place-items: center;
            width: 138px;
            height: 138px;
            border-radius: 50%;
            background: #f3f6ee;
            transform: translate(-50%, -50%);
            text-align: center;
            box-shadow: inset 0 0 0 1px rgba(14, 59, 43, 0.06);
        }

        .member-donut__center span {
            display: block;
            color: #5b6d62;
            font-size: .7rem;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .member-donut__center strong {
            display: block;
            margin-top: 8px;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .member-breakdown__list {
            display: grid;
            gap: 12px;
            align-content: center;
        }

        .member-breakdown__item {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
            min-width: 0;
            padding: 8px 0;
            border-bottom: 1px solid rgba(21, 36, 28, 0.08);
        }

        .member-breakdown__name {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            color: #15241c;
            font-weight: 600;
        }

        .member-breakdown__value {
            color: #5b6d62;
            font-size: .83rem;
            font-weight: 600;
        }

        .member-breakdown__value strong {
            color: #15241c;
            font-weight: 700;
        }

        @media (max-width: 1023px) {
            .member-shell {
                grid-template-columns: 1fr;
            }

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
            .member-value,
            .member-profile__links {
                display: none;
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

            .member-header {
                display: block;
            }

            .member-header__actions {
                margin-top: 18px;
            }

            .member-stats__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .member-breakdown {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 540px) {
            .member-portal {
                padding-top: 32px;
            }

            .member-stats__grid {
                grid-template-columns: 1fr;
            }

            .member-summary__legend {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .member-summary__segment,
            .member-donut__segment,
            .member-stat-card,
            .member-nav__item,
            .member-nav__logout button,
            .member-profile__links a {
                transition: none;
            }
        }
    </style>

    <div class="member-shell page-width">
        <aside class="member-sidebar">
            <div class="member-card member-profile">
                <div class="member-profile__details">
                    <div class="member-avatar" aria-label="Avatar anggota">AW</div>
                    <h2>{{ $member['name'] }}</h2>
                    <p class="member-profile__nip"><x-anggota.masked-id :value="$member['nip']" /></p>
                    <div class="member-divider"></div>
                    <span class="member-label">Jabatan</span>
                    <span class="member-value">{{ $member['jabatan'] }}</span>
                    <div class="member-divider"></div>
                    <div class="member-profile__links">
                        <a href="{{ route('anggota.soon') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.75 4.5 7.5v9l7.5 3.75L19.5 16.5v-9L12 3.75Zm-6 4.5 6 3 6-3M12 11.25v8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Ubah password
                        </a>
                        <a href="{{ route('anggota.soon') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.75a5.25 5.25 0 0 1 5.25 5.25v2.2h.7a2.15 2.15 0 0 1 2.15 2.15v6.1A2.15 2.15 0 0 1 17.95 21.5h-11.9A2.15 2.15 0 0 1 3.9 19.35v-6.1A2.15 2.15 0 0 1 6.05 11.2h.7V9A5.25 5.25 0 0 1 12 3.75Zm0 1.75a3.5 3.5 0 0 0-3.5 3.5v2.2h7V9a3.5 3.5 0 0 0-3.5-3.5Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Ubah avatar
                        </a>
                    </div>
                </div>
            </div>

            <nav class="member-card member-nav" aria-label="Menu anggota">
                <div class="member-nav__list">
                    <a class="member-nav__item is-active" href="{{ route('anggota.dashboard') }}" aria-current="page">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12.5 12 4l8 8.5V20a1 1 0 0 1-1 1h-4v-7H9v7H5a1 1 0 0 1-1-1v-7.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Dashboard
                    </a>
                    <a class="member-nav__item" href="{{ route('anggota.soon') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm2.5 0h11M8 12h8M8 15h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Simpanan
                    </a>
                    <a class="member-nav__item" href="{{ route('anggota.soon') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5h14M7.5 6.5v-.5A1.5 1.5 0 0 1 9 4.5h6a1.5 1.5 0 0 1 1.5 1.5v.5M6 8.5l1.25 9h9.5L18 8.5H6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Pinjaman
                    </a>
                    <a class="member-nav__item" href="{{ route('anggota.soon') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 18V9.5M10 18V6M15 18v-9M20 18V4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        SHU
                    </a>
                    <a class="member-nav__item" href="{{ route('anggota.soon') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm3 1.5h10M8 12h8M8 15h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Pesanan
                    </a>
                </div>
                <div class="member-nav__divider"></div>
                <form class="member-nav__logout" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5.75A1.75 1.75 0 0 1 10.75 4h6.5A1.75 1.75 0 0 1 19 5.75v12.5A1.75 1.75 0 0 1 17.25 20h-6.5A1.75 1.75 0 0 1 9 18.25V17M4 12h11m0 0-3-3m3 3-3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Keluar
                    </button>
                </form>
            </nav>
        </aside>

        <div class="member-main">
            <header class="member-header">
                <div>
                    <p class="member-header__eyebrow">Dashboard anggota</p>
                    <h1>Halo, <span>{{ $member['name'] }}</span></h1>
                    <p class="member-header__subtitle">Ringkasan simpanan dan pinjaman Anda.</p>
                </div>
                <div class="member-header__actions">
                    <a class="button button--primary" href="{{ route('anggota.soon') }}">Setor sukarela</a>
                    <a class="button button--secondary" href="{{ route('anggota.soon') }}">Ajukan pinjaman</a>
                </div>
            </header>

            <section class="member-summary member-card" aria-label="Total simpanan anggota">
                <div class="member-summary__inner">
                    <span class="member-summary__label">Total simpanan</span>
                    <strong class="member-summary__amount" data-count-value="8164614">Rp 8.164.614</strong>
                    <div class="member-summary__bar member-animate" aria-label="Komposisi simpanan: 1,2 persen pokok, 61,9 persen wajib, 19,0 persen sukarela, 17,9 persen khusus">
                        @foreach ($composition as $segment)
                            <span class="member-summary__segment" style="background: {{ $segment['color'] }}; width: {{ $segment['percent'] }}%;" aria-hidden="true"></span>
                        @endforeach
                    </div>
                    <div class="member-summary__legend" aria-label="Legenda komposisi simpanan">
                        @foreach ($composition as $segment)
                            <div class="member-legend-item">
                                <span class="member-legend-item__dot" style="--dot-color: {{ $segment['color'] }}"></span>
                                <div class="member-legend-item__meta">
                                    <span>{{ $segment['name'] }}</span>
                                    <strong>{{ 'Rp ' . number_format($segment['amount'], 0, ',', '.') }} ({{ number_format($segment['percent'], 1, ',', '.') }}%)</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a class="member-summary__link" href="{{ route('anggota.soon') }}">Lihat simpanan</a>
                </div>
            </section>

            <section class="member-stats" aria-label="Ringkasan statistik anggota">
                <div class="member-stats__group">
                    <h2 class="member-stats__group-title">Simpanan</h2>
                    <div class="member-stats__grid">
                        @foreach ($stats['simpanan'] as $item)
                            <article class="member-stat-card member-reveal">
                                <div class="member-stat-card__top">
                                    <span class="member-stat-card__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M12 1.75v20M16.5 5.25c0-1.66-2.01-3-4.5-3s-4.5 1.34-4.5 3 2.01 3 4.5 3 4.5 1.34 4.5 3-2.01 3-4.5 3-4.5-1.34-4.5-3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    {{ strtoupper($item['name']) }}
                                </div>
                                <strong data-count-value="{{ $item['amount'] }}">Rp {{ number_format($item['amount'], 0, ',', '.') }}</strong>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="member-stats__group">
                    <h2 class="member-stats__group-title">Pinjaman</h2>
                    <div class="member-stats__grid">
                        @foreach ($stats['pinjaman'] as $item)
                            <article class="member-stat-card member-reveal">
                                <div class="member-stat-card__top">
                                    <span class="member-stat-card__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Zm2.5 1.5h11M8 12h8M8 15h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    {{ strtoupper($item['name']) }}
                                </div>
                                <strong data-count-value="{{ $item['amount'] }}">Rp {{ number_format($item['amount'], 0, ',', '.') }}</strong>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="member-breakdown member-card" aria-label="Komposisi simpanan">
                <div class="member-breakdown__donut">
                    <div class="member-donut" role="img" aria-label="Komposisi simpanan: pokok 1,2 persen, wajib 61,9 persen, sukarela 19,0 persen, khusus 17,9 persen.">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="member-donut__bg" cx="60" cy="60" r="46"></circle>
                            @php
                                $segments = $composition;
                                $circumference = 2 * pi() * 46;
                            @endphp
                            @foreach ($segments as $index => $segment)
                                @php
                                    $slice = ($segment['percent'] / 100) * $circumference;
                                    $offset = collect($segments)->take($index)->sum(fn ($part) => ($part['percent'] / 100) * $circumference);
                                @endphp
                                <circle
                                    class="member-donut__segment member-animate-donut"
                                    cx="60"
                                    cy="60"
                                    r="46"
                                    stroke="{{ $segment['color'] }}"
                                    stroke-dasharray="{{ $slice }} {{ $circumference - $slice }}"
                                    stroke-dashoffset="{{ -$offset }}"
                                ></circle>
                            @endforeach
                        </svg>
                        <div class="member-donut__center">
                            <div>
                                <span>Total</span>
                                <strong>Rp {{ number_format($totalSimpanan, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="member-breakdown__list" aria-label="Daftar komposisi simpanan">
                    @foreach ($composition as $segment)
                        <div class="member-breakdown__item">
                            <div class="member-breakdown__name">
                                <span class="member-legend-item__dot" style="--dot-color: {{ $segment['color'] }}"></span>
                                <span>{{ $segment['name'] }}</span>
                            </div>
                            <div class="member-breakdown__value"><strong>{{ number_format($segment['percent'], 1, ',', '.') }}%</strong></div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const formatCurrency = (value) => {
                const number = new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 0,
                }).format(value);

                return `Rp ${number}`;
            };

            const animateValue = (element) => {
                const target = Number(element.dataset.countValue ?? 0);
                const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (reducedMotion) {
                    element.textContent = formatCurrency(target);
                    return;
                }

                const duration = 1200;
                const startTimestamp = performance.now();
                const startValue = 0;

                const tick = (timestamp) => {
                    const progress = Math.min(1, (timestamp - startTimestamp) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(startValue + ((target - startValue) * eased));
                    element.textContent = formatCurrency(current);

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            };

            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            document.querySelectorAll('[data-count-value]').forEach((element) => {
                const value = Number(element.dataset.countValue ?? 0);
                element.textContent = formatCurrency(value);
                animateValue(element);
            });

            document.querySelectorAll('.member-reveal, .member-animate, .member-animate-donut').forEach((element) => {
                revealObserver.observe(element);
            });
        })();
    </script>
@endsection
