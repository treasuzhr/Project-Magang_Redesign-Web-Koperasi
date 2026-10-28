<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

$newsArticles = static function (): Collection {
    return collect(config('news.articles', []))->map(static function (array $article): array {
        $article['slug'] ??= Str::slug($article['title']);
        $text = collect($article['sections'] ?? [])->flatMap(static fn (array $section): array => $section['paragraphs'] ?? [])->implode(' ');
        $text = $text ?: ($article['excerpt'] ?? '');
        $excerpt = trim($article['excerpt'] ?? '');
        if ($excerpt === '' || preg_match('/(?:\.{3}|…)\s*$/u', $excerpt) || ! preg_match('/[.!?]["\x{201D}\x{2019}]?$/u', $excerpt)) {
            $source = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
            preg_match('/^.*?[.!?](?:\s|$)/u', $source, $sentence);
            $summary = $sentence[0] ?? $source;
            $article['summary'] = rtrim(Str::limit(trim($summary), 159, '', preserveWords: true), ' .…').'…';
        } else {
            $article['summary'] = $excerpt;
        }
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($text));
        $article['reading_minutes'] = max(1, (int) ceil($wordCount / 200));

        return $article;
    })->unique('slug')->sortByDesc('datetime')->values();
};

Route::get('/', function () {
    $categories = config('product_categories');
    $news = config('news.articles');

    return view('welcome', compact('categories', 'news'));
})->name('home');

Route::get('/berita', function () use ($newsArticles) {
    $filters = request()->validate(['page' => ['nullable', 'integer', 'min:1']]);
    $allArticles = $newsArticles();
    $remaining = $allArticles->slice(1)->values();
    $page = (int) ($filters['page'] ?? 1);
    $articles = new LengthAwarePaginator($remaining->forPage($page, 8)->values(), $remaining->count(), 8, $page, ['path' => route('news.index')]);
    abort_if($page > $articles->lastPage(), 404);

    return view('news.index', [
        'featured' => $allArticles->first(),
        'articles' => $articles,
    ]);
})->name('news.index');

Route::get('/berita/{slug}', function (string $slug) use ($newsArticles) {
    $articles = $newsArticles();
    $article = $articles->firstWhere('slug', $slug);

    abort_if($article === null, 404);

    $related = $articles->where('slug', '!=', $slug)->take(2)->values();

    return view('news.show', compact('article', 'related'));
})->name('news.show');

Route::get('/tentang-kami', function () {
    return view('about.index', [
        'organization' => config('organization'),
    ]);
})->name('about');

Route::redirect('/tentang', '/tentang-kami');

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
