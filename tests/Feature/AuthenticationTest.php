<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::create([
            'name' => 'مدير النظام',
            'username' => 'admin',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret123'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $this->get(route('seasons.index'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        $this->get(route('buyer-payments.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_email(): void
    {
        $user = $this->admin();

        $this->post(route('login.attempt'), [
            'login' => 'admin@agri.local',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        $this->admin();

        $this->post(route('login.attempt'), [
            'login' => 'admin',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->admin();

        $this->post(route('login.attempt'), [
            'login' => 'admin@agri.local',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs($this->admin())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('لوحة التحكم', false);
    }
}
