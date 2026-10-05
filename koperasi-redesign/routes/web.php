<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $categories = [
        ['name' => 'Snack', 'description' => 'Pilihan camilan', 'image' => 'assets/images/categories/snack.png'],
        ['name' => 'Minuman', 'description' => 'Pilihan minuman', 'image' => 'assets/images/categories/minuman.png'],
        ['name' => 'Sembako', 'description' => 'Kebutuhan sehari-hari', 'image' => 'assets/images/categories/sembako.png'],
        ['name' => 'Sabun', 'description' => 'Perawatan kebersihan', 'image' => 'assets/images/categories/sabun.png'],
        ['name' => 'Deterjen', 'description' => 'Kebutuhan rumah tangga', 'image' => 'assets/images/categories/deterjen.png'],
        ['name' => 'Rokok', 'description' => 'Produk pilihan', 'image' => 'assets/images/categories/rokok.png'],
    ];

    $news = [
        [
            'title' => 'Layanan Primkop Pullahta Hankam',
            'date' => '01 Februari 2026',
            'datetime' => '2026-02-01',
            'excerpt' => 'Layanan inti yang membumi. Tulang punggung Primkop adalah layanan simpan dan pinjam. Di sisi simpan...',
            'image' => 'assets/images/news/berita-1.jpg',
        ],
        [
            'title' => 'Keanggotaan, Manfaat, dan Kewajiban',
            'date' => '01 Februari 2026',
            'datetime' => '2026-02-01',
            'excerpt' => 'Hingga 31 Desember 2024, Primkop mencatat 160 anggota aktif semula 157 anggota pada akhir 2023, dengan...',
            'image' => 'assets/images/news/berita-2.png',
        ],
        [
            'title' => 'Rumah Kesejahteraan dan Gotong Royong',
            'date' => '01 Februari 2026',
            'datetime' => '2026-02-01',
            'excerpt' => 'Di lingkungan Pusdatin Kemhan, koperasi bukan sekadar unit usaha, melainkan rumah bagi semangat...',
            'image' => 'assets/images/news/berita-3.jpg',
        ],
    ];

    return view('welcome', compact('categories', 'news'));
});
