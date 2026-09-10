<?php

namespace App\Providers;

use App\Services\AlertService;
use App\Services\BuyerAccountService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // نسخة واحدة لكل طلب: تُحسب أرصدة التجار والتنبيهات مرة واحدة فقط
        $this->app->singleton(BuyerAccountService::class);
        $this->app->singleton(AlertService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // واجهة الترقيم بنمط Bootstrap 5 لتتناسق مع تصميم النظام
        Paginator::useBootstrapFive();

        // تمرير التنبيهات الذكية إلى الشريط العلوي في كل الصفحات
        View::composer('layouts.app', function ($view) {
            $alerts = Auth::check() ? app(AlertService::class)->all() : collect();

            $view->with('navbarAlerts', $alerts);
        });

    }



    <?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
}
