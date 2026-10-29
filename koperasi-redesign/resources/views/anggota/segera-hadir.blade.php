@extends('layouts.anggota')

@section('member-content')
    <div class="member-soon page-width">
        <div class="member-soon__card">
            <p class="member-soon__eyebrow">ANGGOTA</p>
            <h1>Segera hadir</h1>
            <p>Fitur ini sedang dalam tahap pengembangan.</p>
            <a class="button button--primary" href="{{ route('anggota.dashboard') }}">Kembali ke dashboard</a>
        </div>
    </div>

    <style>
        .member-soon {
            display: grid;
            place-items: center;
            min-height: 56vh;
            padding-block: 52px;
        }

        .member-soon__card {
            width: min(100%, 640px);
            padding: 38px 32px;
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 16px 32px rgba(21, 36, 28, 0.06);
            text-align: center;
        }

        .member-soon__eyebrow {
            margin: 0 0 10px;
            color: #7fad39;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .member-soon__card h1 {
            margin: 0;
            color: #15241c;
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            font-weight: 700;
            letter-spacing: -.06em;
        }

        .member-soon__card p {
            margin: 12px auto 20px;
            max-width: 34ch;
            color: #5b6d62;
            font-size: 1rem;
        }
    </style>
@endsection
