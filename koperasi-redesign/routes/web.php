<?php

use App\Http\Controllers\ProductController;
use App\ProductDisplayName;
use App\ProductSource;
use Illuminate\Http\Request;
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

$generateLoginCaptcha = static function (): string {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';

    for ($index = 0; $index < 6; $index++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
};

Route::get('/login', function () {
    if (session()->get('member.authenticated')) {
        return redirect()->route('anggota.dashboard');
    }

    return view('login');
})->name('login');

Route::get('/login/captcha', function () use ($generateLoginCaptcha) {
    $captchaCode = $generateLoginCaptcha();
    session()->put('captcha_answer', $captchaCode);

    $image = imagecreatetruecolor(180, 60);

    $background = imagecolorallocate($image, 245, 247, 250);
    $textColor = imagecolorallocate($image, 40, 50, 65);
    $noiseColor = imagecolorallocate($image, 180, 190, 200);

    imagefill($image, 0, 0, $background);

    for ($i = 0; $i < 8; $i++) {
        imageline(
            $image,
            random_int(0, 240),
            random_int(0, 75),
            random_int(0, 240),
            random_int(0, 75),
            $noiseColor
        );
    }

    $fontPath = 'C:/Windows/Fonts/segoepr.ttf';
$fontSize = 20;

$bbox = imagettfbbox($fontSize, 0, $fontPath, $captchaCode);

$textWidth = $bbox[2] - $bbox[0];
$textHeight = $bbox[1] - $bbox[7];

$x = (180 - $textWidth) / 2;
$y = (60 + $textHeight) / 2;

$colors = [
    imagecolorallocatealpha($image, 39, 112, 76, 25),
    imagecolorallocatealpha($image, 43, 105, 170, 25),
    imagecolorallocatealpha($image, 210, 125, 43, 25),
    imagecolorallocatealpha($image, 183, 65, 82, 25),
    imagecolorallocatealpha($image, 35, 135, 135, 25),
    imagecolorallocatealpha($image, 120, 85, 160, 25)
];

    $charSpacing = 27;
    $startX = (180 - (strlen($captchaCode) * $charSpacing)) / 2;

    for ($i = 0; $i < strlen($captchaCode); $i++) {
    $angle = random_int(-22, 22);
    $positionX = $startX + ($i * $charSpacing) + random_int(-3, 3);
    $positionY = $y + random_int(-8, 8);
    $randomFontSize = $fontSize + random_int(-2, 2);

    imagettftext(
        $image,
        $randomFontSize,
        $angle,
        (int) $positionX,
        (int) $positionY,
        $colors[$i % count($colors)],
        $fontPath,
        $captchaCode[$i]
    );
}

    for ($i = 0; $i < 5; $i++) {
    $noiseColor = imagecolorallocate($image, 150, 160, 150);

    imageline(
        $image,
        random_int(0, 180),
        random_int(0, 60),
        random_int(0, 180),
        random_int(0, 60),
        $noiseColor
    );
}

    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();

    imagedestroy($image);

    return response($imageData)
        ->header('Content-Type', 'image/png')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('login.captcha');

Route::post('/login', function (Request $request) {
    $request->validate([
        'member_id' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:4'],
        'captcha' => ['required', 'string', 'size:6', 'alpha_num'],
    ]);

    $expected = (string) session('captcha_answer', '');
    $submitted = strtoupper((string) $request->input('captcha'));

    if ($expected === '' || ! hash_equals($expected, $submitted)) {
        return back()->withErrors(['captcha' => 'Captcha tidak sesuai.'])->withInput();
    }

    $request->session()->regenerate();
    $request->session()->put('member.authenticated', true);
    $request->session()->put('member.id', trim((string) $request->input('member_id')));
    $request->session()->put('member.name', 'Agus Wiji Suhariono');
    $request->session()->forget('captcha_answer');
    $request->session()->forget('captcha_question');

    return redirect()->route('anggota.dashboard');
})->name('login.submit');

Route::get('/anggota', [App\Http\Controllers\AnggotaController::class, 'dashboard'])->name('anggota.dashboard');
Route::get('/anggota/ubah-password', [App\Http\Controllers\AnggotaController::class, 'passwordEdit'])->name('anggota.password.edit');
Route::post('/anggota/ubah-password', [App\Http\Controllers\AnggotaController::class, 'updatePassword'])->name('anggota.password.update');
Route::get('/anggota/ubah-avatar', [App\Http\Controllers\AnggotaController::class, 'avatarEdit'])->name('anggota.avatar.edit');
Route::post('/anggota/ubah-avatar', [App\Http\Controllers\AnggotaController::class, 'uploadAvatar'])->name('anggota.avatar.upload');
Route::delete('/anggota/ubah-avatar', [App\Http\Controllers\AnggotaController::class, 'deleteAvatar'])->name('anggota.avatar.delete');

Route::get('/anggota/simpanan', [App\Http\Controllers\AnggotaController::class, 'savingsRedirect'])->name('anggota.simpanan.index');
Route::get('/anggota/simpanan/{jenis}/{tahun}', [App\Http\Controllers\AnggotaController::class, 'savingsDetail'])
    ->whereNumber('tahun')
    ->name('anggota.simpanan.detail');
Route::get('/anggota/simpanan/{jenis}', [App\Http\Controllers\AnggotaController::class, 'savingsIndex'])
    ->name('anggota.simpanan.tab');

Route::get('/anggota/shu', [App\Http\Controllers\AnggotaController::class, 'shuIndex'])->name('anggota.shu.index');
Route::get('/anggota/shu/{tahun}', [App\Http\Controllers\AnggotaController::class, 'shuDetail'])
    ->whereNumber('tahun')
    ->name('anggota.shu.detail');

Route::get('/anggota/pinjaman', [App\Http\Controllers\AnggotaController::class, 'loanIndex'])->name('anggota.pinjaman.index');
Route::get('/anggota/pinjaman/ajukan', [App\Http\Controllers\AnggotaController::class, 'loanApplication'])->name('anggota.pinjaman.ajukan');
Route::post('/anggota/pinjaman/ajukan', [App\Http\Controllers\AnggotaController::class, 'submitLoanApplication'])->name('anggota.pinjaman.submit');
Route::get('/anggota/pinjaman/riwayat', [App\Http\Controllers\AnggotaController::class, 'loanHistory'])->name('anggota.pinjaman.riwayat');

Route::get('/anggota/pesanan', [App\Http\Controllers\AnggotaController::class, 'orderIndex'])->name('anggota.pesanan.index');
Route::get('/anggota/pesanan/{id}', [App\Http\Controllers\AnggotaController::class, 'orderDetail'])
    ->whereNumber('id')
    ->name('anggota.pesanan.detail');

Route::get('/anggota/dokumen', [App\Http\Controllers\AnggotaController::class, 'documentIndex'])->name('anggota.dokumen.index');
Route::get('/anggota/dokumen/{id}/unduh', [App\Http\Controllers\AnggotaController::class, 'downloadDocument'])
    ->whereNumber('id')
    ->name('anggota.dokumen.download');

Route::get('/anggota/segera-hadir', function () {
    return view('anggota.segera-hadir');
})->name('anggota.soon');

Route::post('/logout', function () {
    auth()->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();
    request()->session()->forget(['member.authenticated', 'member.id', 'member.name', 'captcha_answer', 'captcha_question']);

    return redirect('/');
})->name('logout');

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
