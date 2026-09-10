<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_reachable_for_guests(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('إنشاء حساب', false);
    }

    public function test_a_farmer_can_register_and_is_logged_in(): void
    {
        $this->post(route('register.store'), [
            'name' => 'أحمد العلي',
            'username' => 'ahmad_ali',
            'email' => 'ahmad.ali@gmail.com',
            'phone' => '0900000000',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('username', 'ahmad_ali')->firstOrFail();

        $this->assertSame(User::ROLE_FARMER, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
    }

    public function test_username_must_be_unique(): void
    {
        User::create([
            'name' => 'مزارع',
            'username' => 'ahmad_ali',
            'email' => 'first@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->post(route('register.store'), [
            'name' => 'مزارع آخر',
            'username' => 'ahmad_ali',
            'email' => 'second@gmail.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertSessionHasErrors('username');

        $this->assertSame(1, User::where('username', 'ahmad_ali')->count());
    }

    public function test_email_must_be_unique_and_password_confirmed(): void
    {
        User::create([
            'name' => 'مزارع',
            'username' => 'first_user',
            'email' => 'taken@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->post(route('register.store'), [
            'name' => 'مزارع آخر',
            'username' => 'second_user',
            'email' => 'taken@gmail.com',
            'password' => 'secret12345',
            'password_confirmation' => 'different12345',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::create([
            'name' => 'مزارع موقوف',
            'username' => 'blocked',
            'email' => 'blocked@gmail.com',
            'password' => Hash::make('secret12345'),
            'is_active' => false,
        ]);

        $this->post(route('login.attempt'), [
            'login' => 'blocked',
            'password' => 'secret12345',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_password_reset_link_is_emailed(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'مزارع',
            'username' => 'farmer_one',
            'email' => 'farmer.one@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->get(route('password.request'))->assertOk();

        $this->post(route('password.email'), ['email' => 'farmer.one@gmail.com'])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::create([
            'name' => 'مزارع',
            'username' => 'farmer_one',
            'email' => 'farmer.one@gmail.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        $this->post(route('login.attempt'), [
            'login' => 'farmer_one',
            'password' => 'new-password-123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::create([
            'name' => 'مزارع',
            'username' => 'farmer_one',
            'email' => 'farmer.one@gmail.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHas('error');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
