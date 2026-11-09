@extends('layouts.public')

@section('title', 'Akses ditolak | Primkop Pullahta Hankam')

@section('content')
    <main class="account-page page-width">
        <p class="section-eyebrow">403</p>
        <h1>Akses ditolak</h1>
        <p>Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <a class="button button--primary" href="{{ auth()->check() ? route(App\Http\Controllers\AuthController::dashboardRoute(auth()->user())) : route('home') }}">Kembali</a>
    </main>
@endsection
