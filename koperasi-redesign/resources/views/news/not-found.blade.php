@extends('layouts.public')

@section('title', 'Berita tidak ditemukan | Primkop Pullahta Hankam')

@push('page-assets')
    @vite('resources/css/news-detail.css')
@endpush

@section('content')
    <main class="news-detail article-redesign news-missing">
        <div class="page-width">
            <p class="section-eyebrow">404</p>
            <h1>Berita tidak ditemukan</h1>
            <p>Berita yang Anda cari belum tersedia atau tautannya sudah berubah.</p>
            <a class="button button--primary" href="{{ route('news.index') }}">Kembali ke berita</a>
        </div>
    </main>
@endsection
