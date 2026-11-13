<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request, LoginCaptchaController $captcha): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->must_change_password ? 'password.edit' : self::dashboardRoute($request->user()));
        }
        $captcha->issue($request);

        return view('login');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->must_change_password ? 'password.edit' : self::dashboardRoute($request->user()));
        }
        $rawNip = $request->input('member_id', '');
        $nip = is_string($rawNip) ? trim($rawNip) : '';
        $key = 'login:'.hash('sha256', mb_strtolower($nip).'|'.$request->ip());
        $ipKey = 'login-ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            return back()->withErrors(['member_id' => 'Terlalu banyak percobaan masuk. Tunggu satu menit lalu coba lagi.'])->withInput($request->only('member_id'));
        }
        RateLimiter::hit($key, 60);
        RateLimiter::hit($ipKey, 60);
        $validator = Validator::make($request->all(), [
            'member_id' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'captcha' => ['required', 'string', 'size:6', 'alpha_num:ascii'],
        ], [
            'member_id.required' => 'NIP wajib diisi.', 'member_id.string' => 'NIP harus berupa teks.', 'member_id.max' => 'NIP maksimal 255 karakter.',
            'password.required' => 'Password wajib diisi.', 'password.string' => 'Password harus berupa teks.', 'password.max' => 'Password terlalu panjang.',
            'captcha.required' => 'Kode verifikasi wajib diisi.', 'captcha.string' => 'Kode verifikasi tidak sesuai.',
            'captcha.size' => 'Kode verifikasi harus berisi 6 karakter.', 'captcha.alpha_num' => 'Kode verifikasi tidak sesuai.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('member_id'));
        }
        $expected = (string) $request->session()->pull('captcha_answer', '');
        if ($expected === '' || ! hash_equals($expected, strtoupper($request->string('captcha')->toString()))) {
            return back()->withErrors(['captcha' => 'Kode verifikasi tidak sesuai.'])->withInput($request->only('member_id'));
        }
        $authenticated = Auth::attemptWhen(['nip' => $nip, 'password' => $request->input('password')],
            fn (User $user): bool => $user->role === 'admin' || ($user->role === 'anggota' && $user->anggota()->where('status', 'aktif')->exists()));
        if (! $authenticated) {
            return back()->withErrors(['member_id' => 'NIP atau password tidak sesuai'])->withInput($request->only('member_id'));
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $intended = $request->session()->get('url.intended');
        if ($request->user()->role === 'anggota' && (is_string($intended) && in_array(parse_url($intended, PHP_URL_PATH), ['/checkout', '/keranjang'], true))) {
            $request->session()->put('checkout.return_to_cart', true);
        }
        $request->session()->forget(['member', 'captcha_question', 'url.intended']);
        if (! $request->user()->must_change_password && $request->user()->role === 'anggota' && $request->session()->pull('checkout.return_to_cart', false)) {
            return redirect()->route('cart.index');
        }

        return redirect()->route($request->user()->must_change_password ? 'password.edit' : self::dashboardRoute($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public static function dashboardRoute(User $user): string
    {
        return $user->role === 'admin' ? 'filament.admin.pages.dashboard' : 'anggota.dashboard';
    }
}
