<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    $categories = config('product_categories');

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
})->name('home');

Route::get('/tentang-kami', function () {
    return view('about.index', [
        'organization' => config('organization'),
    ]);
})->name('about');

Route::get('/produk', function () {
    $categorySlugs = array_column(config('product_categories'), 'slug');
    $filters = request()->validate([
        'q' => ['nullable', 'string', 'max:100'],
        'category' => ['nullable', 'string', Rule::in($categorySlugs)],
        'min_price' => ['nullable', 'numeric', 'min:0'],
        'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
        'sort' => ['nullable', Rule::in(['name_asc', 'name_desc', 'price_asc', 'price_desc'])],
        'view' => ['nullable', Rule::in(['grid', 'list'])],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    return view('products.index', [
        'categories' => config('product_categories'),
        'filters' => $filters,
    ]);
})->name('products.index');
