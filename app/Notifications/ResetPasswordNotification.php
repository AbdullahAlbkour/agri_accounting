<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * رسالة استعادة كلمة المرور بقالب عربي مخصص.
 *
 * تُرسل عبر الطابور (Queue) عندما يكون MAIL_QUEUE_NOTIFICATIONS=true،
 * وإلا تُرسل مباشرة عبر اتصال sync دون الحاجة لتشغيل عامل الطوابير.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);

        // الإرسال المباشر إن لم يُفعّل الطابور
        if (! config('agri.mail.queue_notifications')) {
            $this->connection = 'sync';
        } else {
            $this->queue = config('agri.mail.queue_name');
        }
    }

    public function toMail($notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور - '.config('app.name'))
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $this->resetUrl($notifiable),
                'expireMinutes' => $expireMinutes,
                'appName' => config('app.name'),
            ]);
    }

    /**
     * رابط إعادة التعيين الموقّع بالرمز.
     */
    protected function resetUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
