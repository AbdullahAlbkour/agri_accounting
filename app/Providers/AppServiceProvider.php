<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // واجهة الترقيم بنمط Bootstrap 5 لتتناسق مع تصميم النظام
        Paginator::useBootstrapFive();

        // رسالة استعادة كلمة المرور باللغة العربية
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('إعادة تعيين كلمة المرور - نظام المحاسبة الزراعية')
                ->greeting('مرحباً '.($notifiable->name ?? ''))
                ->line('وصلنا طلب لإعادة تعيين كلمة مرور حسابك في نظام المحاسبة الزراعية.')
                ->action('إعادة تعيين كلمة المرور', url($url))
                ->line('صلاحية هذا الرابط '.$minutes.' دقيقة.')
                ->line('إذا لم تطلب إعادة التعيين فتجاهل هذه الرسالة، ولن يتغير شيء في حسابك.')
                ->salutation('تحياتنا، فريق نظام المحاسبة الزراعية');
        });
    }
}
