<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        app()->setLocale('id');

        try {
            return $next($request);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
