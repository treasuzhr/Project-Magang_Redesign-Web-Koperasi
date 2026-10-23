<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    $categories = config('product_categories');
    $news = config('news.articles');

    return view('welcome', compact('categories', 'news'));
})->name('home');

Route::get('/berita', function () {
    return view('news.index', [
        'featured' => config('news.featured'),
        'articles' => config('news.articles'),
    ]);
})->name('news.index');

Route::get('/berita/{slug}', function (string $slug) {
    $article = collect(config('news.articles'))->firstWhere('slug', $slug);

    abort_if($article === null, 404);

    return view('news.show', compact('article'));
})->where('slug', 'layanan-primkop-pullahta-hankam')->name('news.show');

Route::get('/tentang-kami', function () {
    return view('about.index', [
        'organization' => config('organization'),
    ]);
})->name('about');

Route::view('/kontak', 'contact.index')->name('contact');

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
