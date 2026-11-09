<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke akun anggota Primkop Pullahta Hankam.">
    <title>Masuk Anggota | Primkop Pullahta Hankam</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --green-900: #193322;
            --green-800: #25432f;
            --green-700: #365638;
            --green-600: #638533;
            --green-500: #7fad39;
            --green-300: #c5dba5;
            --green-100: #edf3e6;
            --bg-soft: #f3f6ee;
            --line: #dce4d5;
            --text: #15241c;
            --muted: #5b6d62;
            --white: #ffffff;
            --shadow: 0 16px 36px rgba(21, 36, 28, 0.09);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'DM Sans', 'Inter', sans-serif;
            background: var(--bg-soft);
            color: var(--text);
        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }

        .login-shell {
            width: min(1000px, 100%);
            min-height: 640px;
            display: grid;
            grid-template-columns: 1.04fr 0.96fr;
            background: var(--white);
            border: 1px solid rgba(35, 62, 46, 0.08);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .login-hero {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 28px 40px 24px;
            background: linear-gradient(135deg, #193322 0%, #25432f 58%, #365638 100%);
            color: var(--white);
            isolation: isolate;
        }

        .login-hero::before,
        .login-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            z-index: -1;
        }

        .login-hero::before {
            width: 500px;
            height: 500px;
            bottom: -170px;
            left: -120px;
        }

        .login-hero::after {
            width: 400px;
            height: 400px;
            right: -140px;
            top: -130px;
        }

        .login-hero__mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.08);
            font-size: 22px;
            backdrop-filter: blur(6px);
        }

        .login-hero__content {
            max-width: 540px;
            padding-top: 16px;
        }

        .login-hero__content h1 {
            margin: 0;
            font-size: clamp(1.9rem, 2.8vw, 2.7rem);
            line-height: 1.15;
            letter-spacing: -0.06em;
            font-weight: 700;
        }

        .login-hero__content p {
            margin: 14px 0 0;
            max-width: 460px;
            color: rgba(255, 255, 255, 0.88);
            font-size: 1rem;
            line-height: 1.7;
        }

        .login-hero__footer {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
            margin-top: 24px;
        }

        .login-hero__footer::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.8);
            display: inline-block;
        }

        .login-panel {
            background: #f8faf7;
            padding: 24px 36px 22px;
            display: flex;
            flex-direction: column;
        }

        .login-return {
            color: var(--green-900);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 18px;
            padding-top: 6px;
        }

        .login-brand img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 22px rgba(38, 72, 44, 0.15);
        }

        .login-brand__meta {
            text-align: center;
            line-height: 1.05;
        }

        .login-brand__meta strong {
            display: block;
            font-size: 1.7rem;
            letter-spacing: -0.05em;
            color: var(--green-900);
        }

        .login-brand__meta span {
            display: block;
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            color: rgba(25, 51, 34, 0.7);
            text-transform: uppercase;
        }

        .login-header {
            text-align: left;
            margin: 8px 0 4px;
        }

        .login-header h2 {
            margin: 0;
            color: var(--green-900);
            font-size: clamp(1.55rem, 1.8vw, 2rem);
            line-height: 1.2;
            letter-spacing: -0.05em;
        }

        .login-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .login-form {
            margin-top: 14px;
            display: grid;
            gap: 14px;
        }

        .field-group {
            display: grid;
            gap: 8px;
        }

        .field-group label {
            display: block;
            color: #283b31;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            border: 1px solid #d5ddd4;
            border-radius: 12px;
            background: #ffffff;
            min-height: 48px;
            overflow: hidden;
        }

        .input-wrap .field-icon {
            flex-shrink: 0;
            display: inline-grid;
            place-items: center;
            width: 42px;
            height: 100%;
            font-size: 1.1rem;
            color: #7c8a81;
            background: rgba(124, 138, 129, 0.05);
            border-right: 1px solid rgba(124, 138, 129, 0.15);
        }

        .input-wrap input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text);
            padding: 0 16px 0 14px;
            font-size: 1rem;
        }

        .input-wrap input::placeholder {
            color: #87958d;
        }

        .input-wrap .toggle-password {
            border: 0;
            background: transparent;
            color: #4c5d56;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0 14px 0 0;
            cursor: pointer;
        }

        .captcha-box {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: center;
            min-height: 52px;
            padding: 0 12px 0 14px;
            border: 1px solid #d5ddd4;
            border-radius: 12px;
            background: #fff;
        }

        .captcha-code {
        display: block;
        width: 150px;
        min-width: 150px;
        height: 42px;
        padding: 0;
        object-fit: fill;
        border: 1px solid #dce9ce;
        border-radius: 8px;
        background: #edf7dd;
        }

        .captcha-box input {
            width: 100%;
            border: 0;
            background: transparent;
            outline: none;
            color: var(--text);
            font-size: 1rem;
        }

        .captcha-refresh {
            width: 36px;
            height: 36px;
            border: 1px solid #d8e1d9;
            border-radius: 10px;
            background: #f4f8f5;
            color: var(--green-700);
            font-size: 1.1rem;
            cursor: pointer;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: -2px;
        }

        .remember-me {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .remember-me input {
            width: 15px;
            height: 15px;
            accent-color: var(--green-600);
        }

        .login-options a {
            color: var(--green-800);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .login-submit {
            width: 100%;
            appearance: none;
            border: 0;
            border-radius: 10px;
            background: var(--green-500);
            color: #ffffff;
            min-height: 48px;
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(127, 173, 57, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(127, 173, 57, 0.28);
        }

        .login-help {
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
            margin: -2px 0 0;
        }

        .login-help a {
            color: var(--green-800);
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 920px) {
            .login-shell {
                grid-template-columns: 1fr;
                width: min(680px, 100%);
                min-height: 0;
            }

            .login-panel {
                padding: 24px 28px;
            }

            .login-hero {
                min-height: 260px;
                padding: 24px 28px 20px;
            }

            .login-hero__content {
                padding-top: 24px;
            }

            .login-hero__footer {
                margin-top: 20px;
                padding-top: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .login-shell {
                border-radius: 18px;
            }

            .login-hero {
                min-height: 230px;
                padding: 20px 20px 18px;
            }

            .login-hero__content h1 {
                font-size: 1.8rem;
            }

            .login-panel {
                padding: 20px;
            }

            .login-brand {
                justify-content: flex-start;
            }

            .login-header h2 {
                font-size: 1.55rem;
            }

            .captcha-box {
                gap: 8px;
                padding-inline: 8px;
            }

            .captcha-code {
            width: 200px;
            min-width: 200px;
            height: 65px;
            padding: 0;
            }

            .captcha-box input {
                min-width: 0;
                font-size: 0.9rem;
            }

            .login-options {
                align-items: flex-start;
                gap: 10px;
                flex-direction: column;
            }
        }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Figtree:wght@400;500;600;700&display=swap');

        :root {
            --login-forest: var(--home-forest, #193322);
            --login-leaf: var(--brand, #7fad39);
            --login-paper: #f3f6ee;
            --login-text: #15241c;
            --login-muted: #52635a;
            --login-line: #cfdacd;
            --login-error: #b42318;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            background: var(--login-paper);
            color: var(--login-text);
            font-family: 'Figtree', sans-serif;
        }

        body {
            display: grid;
            min-height: 100vh;
            min-height: 100svh;
            place-items: center;
            padding: 20px;
        }

        .login-shell {
            display: grid;
            grid-template-columns: 42% 58%;
            width: min(1040px, 100%);
            min-height: 520px;
            overflow: hidden;
            border: 1px solid rgba(14, 59, 43, .08);
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(21, 36, 28, .1);
        }

        .login-hero {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 44px;
            overflow: hidden;
            isolation: isolate;
            background: var(--login-forest);
            color: #fff;
            text-align: center;
        }

        .login-hero::before,
        .login-hero::after {
            content: none;
        }

        .login-hero__content {
            max-width: 390px;
            padding: 0;
        }

        .login-hero__eyebrow {
            margin: 0 0 12px;
            color: #b8d98b;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .16em;
        }

        .login-hero__content h1 {
            margin: 0;
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(2rem, 3vw, 2.5rem);
            font-weight: 700;
            letter-spacing: -.045em;
            line-height: 1.12;
            text-align: center;
        }

        .login-hero__content h1 span {
            color: var(--login-leaf);
        }

        .login-hero__content p {
            max-width: 370px;
            margin: 16px auto 0;
            color: rgba(255, 255, 255, .84);
            font-size: 1rem;
            line-height: 1.6;
            text-align: center;
        }

        .login-panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding: 62px 48px 32px;
            background: #fff;
        }

        .login-return {
            position: absolute;
            top: 24px;
            left: 40px;
            display: inline-flex;
            min-height: 32px;
            align-items: center;
            gap: 7px;
            margin: 0;
            color: var(--login-muted);
            font-size: .86rem;
            font-weight: 600;
            text-decoration: none;
        }

        .login-return:hover {
            color: var(--login-forest);
        }

        .login-return svg,
        .field-icon svg,
        .toggle-password svg,
        .captcha-refresh svg {
            display: block;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .login-content {
            width: min(100%, 400px);
            margin: auto;
        }

        .login-brand {
            display: flex;
            justify-content: flex-start;
            margin: 0 0 16px;
            padding: 0;
        }

        .login-brand img {
            display: block;
            width: 152px;
            height: 44px;
            border-radius: 0;
            object-fit: contain;
            box-shadow: none;
        }

        .login-header {
            margin: 0;
            text-align: left;
        }

        .login-header h2 {
            margin: 0;
            color: var(--login-text);
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(1.65rem, 2.4vw, 1.9rem);
            font-weight: 700;
            letter-spacing: -.04em;
            line-height: 1.15;
        }

        .login-header p {
            margin: 7px 0 0;
            color: var(--login-muted);
            font-size: .92rem;
            line-height: 1.45;
        }

        .login-form {
            display: grid;
            gap: 13px;
            margin-top: 16px;
        }

        .login-summary {
            margin: 0;
            padding: 10px 12px;
            border: 1px solid rgba(180, 35, 24, .2);
            border-radius: 12px;
            background: #fef3f2;
            color: #8a1c13;
            font-size: .84rem;
            line-height: 1.4;
        }

        .login-summary[hidden] {
            display: none;
        }

        .field-group {
            display: grid;
            min-width: 0;
            gap: 6px;
        }

        .field-group label {
            display: block;
            color: #25382d;
            font-size: .875rem;
            font-weight: 700;
        }

        .input-wrap {
            display: flex;
            min-width: 0;
            min-height: 48px;
            align-items: center;
            overflow: hidden;
            border: 1px solid var(--login-line);
            border-radius: 14px;
            background: #fff;
        }

        .input-wrap:focus-within,
        .captcha-box:focus-within {
            border-color: #6a9632;
            box-shadow: 0 0 0 3px rgba(106, 150, 50, .2);
        }

        .input-wrap:has(input[aria-invalid="true"]),
        .captcha-box:has(input[aria-invalid="true"]) {
            border-color: var(--login-error);
        }

        .field-icon {
            display: grid;
            width: 44px;
            height: 46px;
            flex: 0 0 44px;
            place-items: center;
            color: #607367;
        }

        .input-wrap input,
        .captcha-box input {
            width: 100%;
            min-width: 0;
            height: 46px;
            padding: 0 12px 0 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--login-text);
            font: inherit;
            font-size: .95rem;
        }

        .input-wrap input::placeholder,
        .captcha-box input::placeholder {
            color: #66776c;
            opacity: 1;
        }

        .toggle-password,
        .captcha-refresh {
            display: grid;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            place-items: center;
            border: 0;
            background: transparent;
            color: var(--login-muted);
            cursor: pointer;
        }

        .toggle-password:hover,
        .captcha-refresh:hover {
            color: var(--login-forest);
        }

        .toggle-password:focus-visible,
        .captcha-refresh:focus-visible,
        .login-submit:focus-visible,
        .login-return:focus-visible {
            outline: 3px solid var(--login-leaf);
            outline-offset: 3px;
        }

        .captcha-box {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr) 36px;
        min-width: 0;
        min-height: 50px;
        align-items: center;
        gap: 8px;
        padding: 3px 8px;
        border: 1px solid var(--login-line);
        border-radius: 12px;
        background: #fff;
        }

        .captcha-code {
        display: block;
        width: 150px;
        min-width: 150px;
        height: 42px;
        padding: 0;
        object-fit: fill;
        border: 1px solid #dce9ce;
        border-radius: 8px;
        background: #edf7dd;
        }

        .captcha-box input {
            padding: 0 2px;
        }

        .captcha-refresh {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            border: 1px solid #dce4d5;
            border-radius: 10px;
            background: #f7f9f4;
        }

        .field-error {
            margin: 0;
            color: var(--login-error);
            font-size: .8rem;
            line-height: 1.35;
        }

        .login-submit {
            width: 100%;
            min-height: 48px;
            margin-top: 2px;
            border: 0;
            border-radius: 999px;
            background: var(--login-leaf);
            color: #14251b;
            font: inherit;
            font-size: .95rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform .15s ease, background-color .15s ease;
        }

        .login-submit:hover {
            transform: translateY(-1px);
            background: #91bb50;
        }

        .login-submit:disabled {
            cursor: wait;
            opacity: .75;
        }

        @media (prefers-reduced-motion: no-preference) {
            .login-shell {
                animation: login-enter .55s cubic-bezier(.2, .8, .2, 1) both;
            }

            @keyframes login-enter {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }

        @media (max-width: 1023px) {
            .login-shell {
                grid-template-columns: 1fr;
                width: min(520px, 100%);
                min-height: 0;
            }

            .login-hero {
                display: none;
            }

            .login-panel {
                min-height: 0;
                padding: 64px 28px 28px;
            }

            .login-return {
                top: 18px;
                left: 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .login-shell {
                border-radius: 22px;
            }

            .login-panel {
                padding: 60px 20px 22px;
            }

            .login-return {
                left: 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-submit {
                transition: none;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell" aria-label="Halaman masuk anggota">
        <section class="login-hero" aria-label="Layanan anggota">
            <div class="login-hero__content">
                <p class="login-hero__eyebrow">UNTUK ANGGOTA</p>
                <h1>Layanan anggota dalam <span>satu akses.</span></h1>
                <p>
                    Pantau simpanan, pinjaman, SHU, dan pesanan produk melalui akun anggota Primkop.
                </p>
            </div>
        </section>

        <section class="login-panel" aria-label="Form masuk akun anggota">
            <a class="login-return" href="{{ route('home') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 18-6-6 6-6"></path><path d="M8 12h12"></path></svg>
                <span>Kembali ke beranda</span>
            </a>

            <div class="login-content">
                <div class="login-brand" aria-label="Logo Primkop">
                    <img src="{{ asset('assets/images/logo/Logo PRIMKOP.png') }}" alt="Logo Primkop">
                </div>

                <div class="login-header">
                    <h2>Masuk ke akun anggota</h2>
                    <p>Masuk dengan NRP/NIP yang terdaftar.</p>
                </div>

                <form class="login-form" method="POST" action="{{ route('login.submit') }}" id="loginForm">
                    @csrf

                    <div class="login-summary" role="alert" data-login-alert @if (!$errors->any()) hidden @endif>
                        @if ($errors->any())
                            Periksa kembali data yang ditandai lalu coba masuk kembali.
                        @endif
                    </div>

                    <div class="field-group">
                        <label for="memberId">NRP / NIP</label>
                        <div class="input-wrap">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.5-3.5 2.8-5.3 7-5.3s6.5 1.8 7 5.3"></path></svg>
                            </span>
                            <input id="memberId" name="member_id" type="text" placeholder="Masukkan NRP / NIP" autocomplete="username" value="{{ old('member_id') }}" required @if ($errors->has('member_id')) aria-invalid="true" aria-describedby="memberIdError" @endif>
                        </div>
                        @if ($errors->has('member_id'))
                            <p class="field-error" id="memberIdError">{{ $errors->first('member_id') }}</p>
                        @endif
                    </div>

                    <div class="field-group">
                        <label for="password">Kata sandi</label>
                        <div class="input-wrap">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v3"></path></svg>
                            </span>
                            <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required @if ($errors->has('password')) aria-invalid="true" aria-describedby="passwordError" @endif>
                            <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi" data-toggle-password>
                                <svg viewBox="0 0 24 24" data-eye-open aria-hidden="true"><path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                <svg viewBox="0 0 24 24" data-eye-closed aria-hidden="true" hidden><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.3 0 9.5 6 9.5 6a15 15 0 0 1-3.1 3.8"></path><path d="M6.2 6.2C3.8 7.8 2.5 12 2.5 12s3.2 6 9.5 6c1 0 2-.2 2.8-.5"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                            <p class="field-error" id="passwordError">{{ $errors->first('password') }}</p>
                        @endif
                    </div>

                    <div class="field-group">
                        <label for="captchaInput">Kode verifikasi</label>
                        <div class="captcha-box">
                            <img src="{{ route('login.captcha', ['image' => 1]) }}" id="captchaCode" alt="Kode verifikasi CAPTCHA" class="captcha-code">
                            <input type="text" name="captcha" id="captchaInput" placeholder="Kode pada gambar" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="6" value="{{ old('captcha') }}" required @if ($errors->has('captcha')) aria-invalid="true" aria-describedby="captchaError" @endif>
                            <button type="button" class="captcha-refresh" id="refreshCaptcha" aria-label="Segarkan kode verifikasi">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5"></path><path d="M19 12a7 7 0 0 0-12-5L5 9"></path><path d="M4 17v-5h5"></path><path d="M5 12a7 7 0 0 0 12 5l2-2"></path></svg>
                            </button>
                        </div>
                        @if ($errors->has('captcha'))
                            <p class="field-error" id="captchaError">{{ $errors->first('captcha') }}</p>
                        @endif
                    </div>

                    <button class="login-submit" type="submit"><span data-submit-label>Masuk</span></button>
                </form>
            </div>
        </section>
    </main>

    <script>
        const captchaCode = document.getElementById('captchaCode');
        const captchaInput = document.getElementById('captchaInput');
        const refreshCaptchaButton = document.getElementById('refreshCaptcha');
        const loginAlert = document.querySelector('[data-login-alert]');
        const loginForm = document.getElementById('loginForm');

        async function refreshCaptcha(shouldFocus = true) {
            refreshCaptchaButton.disabled = true;
            try {
                const response = await fetch("{{ route('login.captcha') }}", {
                    headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Kode verifikasi belum dapat diperbarui. Coba lagi.');
                const result = await response.json();
                if (!result.code) throw new Error('Kode verifikasi belum tersedia. Coba lagi.');
                captchaCode.src = "{!! route('login.captcha', ['image' => 1]) !!}&t=" + Date.now();
                captchaInput.value = '';
                if (shouldFocus) captchaInput.focus();
            } finally {
                refreshCaptchaButton.disabled = false;
            }
        }

        function showCaptchaError(error) {
            loginAlert.textContent = error.message;
            loginAlert.hidden = false;
        }

        refreshCaptchaButton.addEventListener('click', function () {
            refreshCaptcha().catch(showCaptchaError);
        });

        document.querySelector('[data-toggle-password]').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            this.querySelector('[data-eye-open]').hidden = isPassword;
            this.querySelector('[data-eye-closed]').hidden = !isPassword;
        });

        loginForm.addEventListener('submit', function () {
            const submitButton = loginForm.querySelector('.login-submit');
            submitButton.disabled = true;
            submitButton.querySelector('[data-submit-label]').textContent = 'Memproses...';
        });

        const firstInvalidField = document.querySelector('.login-form [aria-invalid="true"]');
        if (firstInvalidField) {
            firstInvalidField.focus();
        }

    </script>
</body>
</html>
