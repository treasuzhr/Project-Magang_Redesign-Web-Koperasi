<?php

namespace App\Providers;

use App\ProductSource;
use App\SampleProductSource;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductSource::class, SampleProductSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('loan-application', fn (Request $request) => Limit::perMinute(10)->by('loan-application:'.$request->user()->id)
            ->response(fn () => response('Terlalu banyak percobaan pengajuan. Coba lagi dalam satu menit.', 429))
        );
    }
}
