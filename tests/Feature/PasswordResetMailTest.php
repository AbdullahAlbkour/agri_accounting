<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * قالب رسالة استعادة كلمة المرور وطريقة إرسالها (مباشر أو عبر الطابور).
 */
class PasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    protected function farmer(): User
    {
        return User::create([
            'name' => 'أحمد العلي',
            'username' => 'ahmad_ali',
            'email' => 'ahmad.ali@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);
    }

    public function test_reset_mail_uses_the_arabic_template(): void
    {
        $user = $this->farmer();

        $mail = (new ResetPasswordNotification('token-123'))->toMail($user);
        $rendered = $mail->render();

        $this->assertStringContainsString('إعادة تعيين كلمة المرور', $mail->subject);
        $this->assertStringContainsString('emails.reset-password', $mail->view);
        $this->assertStringContainsString('dir="rtl"', $rendered);
        $this->assertStringContainsString('أحمد العلي', $rendered);
        $this->assertStringContainsString('reset-password/token-123', $rendered);
        $this->assertStringContainsString('نظام المحاسبة الزراعية', $rendered);
    }

    public function test_notification_is_sent_directly_when_queue_is_disabled(): void
    {
        config(['agri.mail.queue_notifications' => false]);

        $notification = new ResetPasswordNotification('token-123');

        $this->assertSame('sync', $notification->connection);
    }

    public function test_notification_is_queued_when_enabled(): void
    {
        config(['agri.mail.queue_notifications' => true, 'agri.mail.queue_name' => 'emails']);

        $notification = new ResetPasswordNotification('token-123');

        $this->assertNull($notification->connection);
        $this->assertSame('emails', $notification->queue);
    }

    public function test_reset_mail_is_actually_delivered_through_the_mailer(): void
    {
        $user = $this->farmer();

        // النقل في بيئة الاختبار من نوع array، فنقرأ الرسائل الفعلية التي خرجت من النظام
        $transport = Mail::getSymfonyTransport();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('success');

        $messages = $transport->messages();

        $this->assertCount(1, $messages);

        $sent = $messages[0]->getOriginalMessage();
        $body = $sent->getHtmlBody();

        $this->assertSame($user->email, $sent->getTo()[0]->getAddress());
        $this->assertStringContainsString('إعادة تعيين كلمة المرور', $sent->getSubject());
        $this->assertStringContainsString('dir="rtl"', $body);
        $this->assertStringContainsString($user->name, $body);
        $this->assertStringContainsString('/reset-password/', $body);
    }

    public function test_full_reset_flow_uses_the_link_from_the_notification(): void
    {
        Notification::fake();

        $user = $this->farmer();

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }
}
