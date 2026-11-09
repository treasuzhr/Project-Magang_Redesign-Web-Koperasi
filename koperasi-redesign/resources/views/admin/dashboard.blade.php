@extends('layouts.public')

@section('title', 'Dashboard Admin | Primkop Pullahta Hankam')

@section('content')
    <main class="account-page page-width">
        <p class="section-eyebrow">ADMIN</p>
        <h1>Dashboard Admin</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-anggota.primary-button type="submit">Keluar</x-anggota.primary-button>
        </form>
    </main>
@endsection
