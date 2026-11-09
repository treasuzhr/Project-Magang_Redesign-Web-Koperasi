<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('anggota.ubah-password', ['member' => [
            'name' => $request->user()->name, 'nip' => $request->user()->nip,
            'jabatan' => '', 'avatar_url' => null,
        ]]);
    }

    public function update(Request $request): RedirectResponse
    {
        $key = 'account-password:'.$request->user()->id.'|'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return back()->withErrors(['password' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.']);
        }
        RateLimiter::hit($key, 60);
        $validator = Validator::make($request->only('password', 'password_confirmation'), [
            'password' => ['required', 'string', 'min:8', 'max:1024', function (string $attribute, mixed $value, Closure $fail) use ($request): void {
                if (is_string($value) && Hash::check($value, $request->user()->password)) {
                    $fail('Password baru harus berbeda dari password lama.');
                }
            }],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ], [
            'password.required' => 'Password baru wajib diisi.', 'password.string' => 'Password baru harus berupa teks.',
            'password.min' => 'Password baru minimal 8 karakter.', 'password.max' => 'Password baru terlalu panjang.',
            'password_confirmation.required' => 'Ulangi password baru wajib diisi.',
            'password_confirmation.string' => 'Konfirmasi password harus berupa teks.',
            'password_confirmation.same' => 'Kedua password harus sama. Ketik ulang dengan teliti.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        $request->user()->forceFill(['password' => Hash::make($request->input('password')), 'must_change_password' => false])->save();
        $request->session()->regenerate();
        RateLimiter::clear($key);

        return redirect()->route(AuthController::dashboardRoute($request->user()))->with('status', 'Password berhasil diubah.');
    }
}
