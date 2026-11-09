<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginCaptchaController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProductController;
use App\ProductDisplayName;
use App\ProductSource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$newsArticles = static function (): Collection {
    return collect(config('news.articles', []))->map(static function (array $article): array {
        $article['slug'] ??= Str::slug($article['title']);
        $text = collect($article['sections'] ?? [])->flatMap(static fn (array $section): array => $section['paragraphs'] ?? [])->implode(' ');
        $text = $text ?: ($article['excerpt'] ?? '');
        $excerpt = trim($article['excerpt'] ?? '');
        if (! empty($article['sections']) || $excerpt === '' || preg_match('/(?:\.{3}|…)\s*$/u', $excerpt) || ! preg_match('/[.!?]["\x{201D}\x{2019}]?$/u', $excerpt)) {
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

    if ($article === null) {
        return response()->view('news.not-found', [], 404);
    }

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

Route::get('/produk', ProductController::class)->name('products.index');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.submit');
Route::get('/login/captcha', LoginCaptchaController::class)
    ->middleware('throttle:60,1')->name('login.captcha');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/ganti-password', [PasswordController::class, 'update'])->name('password.update');
    Route::view('/admin', 'admin.dashboard')->middleware('role:admin')->name('admin.dashboard');
});

Route::middleware(['auth', 'role:anggota'])->group(function () {
    Route::get('/anggota', [AnggotaController::class, 'dashboard'])->name('anggota.dashboard');
    Route::get('/anggota/ubah-password', [PasswordController::class, 'edit'])->name('anggota.password.edit');
    Route::post('/anggota/ubah-password', [PasswordController::class, 'update'])->name('anggota.password.update');
    Route::get('/anggota/ubah-avatar', [AnggotaController::class, 'avatarEdit'])->name('anggota.avatar.edit');
    Route::post('/anggota/ubah-avatar', [AnggotaController::class, 'uploadAvatar'])->name('anggota.avatar.upload');
    Route::delete('/anggota/ubah-avatar', [AnggotaController::class, 'deleteAvatar'])->name('anggota.avatar.delete');

    Route::get('/anggota/simpanan', [AnggotaController::class, 'savingsRedirect'])->name('anggota.simpanan.index');
    Route::get('/anggota/simpanan/{jenis}/{tahun}', [AnggotaController::class, 'savingsDetail'])
        ->whereNumber('tahun')
        ->name('anggota.simpanan.detail');
    Route::get('/anggota/simpanan/{jenis}', [AnggotaController::class, 'savingsIndex'])
        ->name('anggota.simpanan.tab');

    Route::get('/anggota/shu', [AnggotaController::class, 'shuIndex'])->name('anggota.shu.index');
    Route::get('/anggota/shu/{tahun}', [AnggotaController::class, 'shuDetail'])
        ->whereNumber('tahun')
        ->name('anggota.shu.detail');

    Route::get('/anggota/pinjaman', [AnggotaController::class, 'loanIndex'])->name('anggota.pinjaman.index');
    Route::get('/anggota/pinjaman/ajukan', [AnggotaController::class, 'loanApplication'])->name('anggota.pinjaman.ajukan');
    Route::post('/anggota/pinjaman/ajukan', [AnggotaController::class, 'submitLoanApplication'])->name('anggota.pinjaman.submit');
    Route::get('/anggota/pinjaman/riwayat', [AnggotaController::class, 'loanHistory'])->name('anggota.pinjaman.riwayat');

    Route::get('/anggota/pesanan', [AnggotaController::class, 'orderIndex'])->name('anggota.pesanan.index');
    Route::get('/anggota/pesanan/{id}', [AnggotaController::class, 'orderDetail'])
        ->whereNumber('id')
        ->name('anggota.pesanan.detail');

    Route::get('/anggota/dokumen', [AnggotaController::class, 'documentIndex'])->name('anggota.dokumen.index');
    Route::get('/anggota/dokumen/{id}/unduh', [AnggotaController::class, 'downloadDocument'])
        ->whereNumber('id')
        ->name('anggota.dokumen.download');

    Route::get('/anggota/segera-hadir', function () {
        return view('anggota.segera-hadir');
    })->name('anggota.soon');

});

Route::get('/keranjang', function (ProductSource $source) {
    $categoryLabels = array_column(config('product_categories', []), 'name', 'slug');
    try {
        $products = $source->all()->map(static fn (array $product): array => array_merge($product, [
            'display_name' => ProductDisplayName::format($product['name']),
            'category_label' => $categoryLabels[$product['category'] ?? ''] ?? null,
        ]));
        $sourceAvailable = true;
    } catch (Throwable $exception) {
        report($exception);
        $products = collect();
        $sourceAvailable = false;
    }

    return view('cart.index', compact('products', 'sourceAvailable'));
})->name('cart.index');
