<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ($user->role !== 'admin' && ($user->role !== 'anggota' || ! $user->anggota()->where('status', 'aktif')->exists()))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['member_id' => 'NIP atau password tidak sesuai']);
        }
        if ($user?->must_change_password && ! $request->routeIs('password.*', 'anggota.password.*', 'logout')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
