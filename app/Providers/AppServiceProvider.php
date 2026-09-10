<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
    {
        if (!app()->runningInConsole() && (config('app.env') === 'production' || env('APP_ENV') === 'production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}