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
            --green-900: #1a3d2c;
            --green-800: #214b34;
            --green-700: #2c5b3d;
            --green-600: #3a7f4a;
            --green-500: #4b9a57;
            --green-300: #b4d977;
            --green-100: #e9f5e6;
            --bg-soft: #ecf0ec;
            --line: #d9e2d8;
            --text: #1d2a24;
            --muted: #5f6d66;
            --white: #ffffff;
            --shadow: 0 20px 45px rgba(17, 41, 29, 0.12);
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
            padding: 40px 16px;
        }

        .login-shell {
            width: min(1180px, 100%);
            min-height: 760px;
            display: grid;
            grid-template-columns: 1.04fr 0.96fr;
            background: var(--white);
            border: 1px solid rgba(35, 62, 46, 0.08);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .login-hero {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 32px 52px 26px;
            background: linear-gradient(135deg, #214f35 0%, #2a5d3c 32%, #326d42 100%);
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
            padding-top: 20px;
        }

        .login-hero__content h1 {
            margin: 0;
            font-size: clamp(2.2rem, 3vw, 3.2rem);
            line-height: 1.15;
            letter-spacing: -0.06em;
            font-weight: 700;
        }

        .login-hero__content p {
            margin: 18px 0 0;
            max-width: 460px;
            color: rgba(255, 255, 255, 0.88);
            font-size: 1.08rem;
            line-height: 1.7;
        }

        .login-hero__footer {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            padding-top: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
            margin-top: 32px;
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
            padding: 30px 44px 28px;
            display: flex;
            flex-direction: column;
        }

        .login-return {
            color: #1f2b25;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 22px;
            padding-top: 10px;
        }

        .login-brand img {
            width: 54px;
            height: 54px;
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
            font-size: 2rem;
            letter-spacing: -0.05em;
            color: #20462f;
        }

        .login-brand__meta span {
            display: block;
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            color: rgba(32, 70, 47, 0.7);
            text-transform: uppercase;
        }

        .login-header {
            text-align: left;
            margin: 8px 0 4px;
        }

        .login-header h2 {
            margin: 0;
            color: #1a2f27;
            font-size: clamp(1.8rem, 2vw, 2.45rem);
            line-height: 1.2;
            letter-spacing: -0.05em;
        }

        .login-header p {
            margin: 12px 0 0;
            color: #5b6b63;
            font-size: 1rem;
            line-height: 1.6;
        }

        .login-form {
            margin-top: 18px;
            display: grid;
            gap: 18px;
        }

        .field-group {
            display: grid;
            gap: 8px;
        }

        .field-group label {
            display: block;
            color: #283b31;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            border: 1px solid #d5ddd4;
            border-radius: 12px;
            background: #ffffff;
            min-height: 52px;
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
            min-width: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 12px;
            border-radius: 10px;
            background: #edf7ea;
            border: 1px solid #cfe2d0;
            color: #1d3d2d;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: 0.08em;
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
            color: #365947;
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
            color: #415b51;
            font-size: 0.95rem;
        }

        .remember-me input {
            width: 15px;
            height: 15px;
            accent-color: var(--green-600);
        }

        .login-options a {
            color: #274c3b;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .login-submit {
            width: 100%;
            appearance: none;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #507e4c, #4a8d4d 55%, #6fa835);
            color: #ffffff;
            min-height: 54px;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(76, 140, 88, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(76, 140, 88, 0.32);
        }

        .login-help {
            text-align: center;
            color: #4e635a;
            font-size: 0.92rem;
            margin: -2px 0 0;
        }

        .login-help a {
            color: #224e39;
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 920px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .login-panel {
                padding-inline: 22px;
            }

            .login-hero {
                min-height: 360px;
                padding: 28px 22px 22px;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell" aria-label="Halaman masuk anggota">
        <section class="login-hero" aria-label="Layanan anggota">
            <div class="login-hero__mark" aria-hidden="true">◌</div>

            <div class="login-hero__content">
                <h1>Layanan anggota dalam satu akses.</h1>
                <p>
                    Pantau simpanan, pinjaman, SHU, dan pesanan produk melalui akun anggota Primkop.
                </p>
            </div>

            <div class="login-hero__footer">Akun dikelola oleh admin Primkop</div>
        </section>

        <section class="login-panel" aria-label="Form masuk akun anggota">
            <a class="login-return" href="{{ route('home') }}">&lt; Kembali ke beranda</a>

            <div class="login-brand" aria-label="Logo Primkop">
                <img src="{{ asset('assets/images/logo/Logo PRIMKOP.png') }}" alt="Logo Primkop">
                <div class="login-brand__meta">
                    <strong>PRIMKOP</strong>
                    <span>Member Portal</span>
                </div>
            </div>

            <div class="login-header">
                <h2>Masuk ke akun anggota</h2>
                <p>Gunakan ID anggota yang telah didaftarkan oleh admin.</p>
            </div>

            <form class="login-form" method="POST" action="{{ route('login.submit') }}" id="loginForm">
                @csrf

                @if ($errors->any())
                    <div role="alert" style="padding: 10px 12px; border-radius: 10px; background: rgba(196, 82, 73, 0.08); border: 1px solid rgba(196, 82, 73, 0.2); color: #8a2d2d; font-size: 0.9rem;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="field-group">
                    <label for="memberId">ID Anggota / NIP</label>
                    <div class="input-wrap">
                        <span class="field-icon" aria-hidden="true">◌</span>
                        <input id="memberId" name="member_id" type="text" placeholder="Masukkan ID anggota" autocomplete="username" value="{{ old('member_id') }}" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-wrap">
                        <span class="field-icon" aria-hidden="true">◈</span>
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" value="{{ old('password') }}" required>
                        <button type="button" class="toggle-password" aria-label="Lihat kata sandi" data-toggle-password>lihat</button>
                    </div>
                </div>

                <div class="field-group">
                    <label>Verifikasi keamanan</label>
                    <div class="captcha-box">
                        <div class="captcha-code" id="captchaQuestion" aria-live="polite">{{ $captchaQuestion ?? '8 + 4' }}</div>
                        <input type="text" name="captcha" id="captchaInput" placeholder="Hasil perhitungan" inputmode="numeric" aria-label="Masukkan jawaban captcha" value="{{ old('captcha') }}" required>
                        <button type="button" class="captcha-refresh" id="refreshCaptcha" aria-label="Refresh captcha">↻</button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Ingat akun saya</span>
                    </label>
                    <a href="{{ route('home') }}">Lupa kata sandi?</a>
                </div>

                <button class="login-submit" type="submit">Masuk</button>
            </form>

            <p class="login-help">Belum memiliki akses? <a href="{{ route('contact') }}">Hubungi admin</a>.</p>
        </section>
    </main>

    <script>
        const captchaQuestion = document.getElementById('captchaQuestion');
        const captchaInput = document.getElementById('captchaInput');
        const refreshCaptchaButton = document.getElementById('refreshCaptcha');

        async function refreshCaptcha() {
            const response = await fetch('{{ route('login.captcha') }}');
            if (!response.ok) {
                throw new Error('Captcha tidak dapat diperbarui.');
            }

            const data = await response.json();
            captchaQuestion.textContent = data.question;
            captchaInput.value = '';
            captchaInput.focus();
        }

        refreshCaptchaButton.addEventListener('click', refreshCaptcha);

        document.querySelector('[data-toggle-password]').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? 'sembunyikan' : 'lihat';
        });

    </script>
</body>
</html>
