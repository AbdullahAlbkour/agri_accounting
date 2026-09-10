<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Field;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $username, string $role = User::ROLE_FARMER): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'email' => $username.'@gmail.com',
            'password' => Hash::make('secret12345'),
            'role' => $role,
        ]);
    }

    public function test_farmer_cannot_reach_admin_area_or_backup(): void
    {
        $farmer = $this->user('farmer_a');

        $this->actingAs($farmer)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($farmer)->get(route('backup.download'))->assertForbidden();
    }

    public function test_admin_sees_all_accounts(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $this->user('farmer_a');
        $this->user('farmer_b');

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('farmer_a')
            ->assertSee('farmer_b');
    }

    public function test_admin_forms_render(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $farmer = $this->user('farmer_a');

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $farmer))->assertOk()->assertSee('farmer_a');
    }

    public function test_admin_can_create_a_farmer_account(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'مزارع جديد',
            'username' => 'new_farmer',
            'email' => 'new.farmer@gmail.com',
            'role' => 'farmer',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::ROLE_FARMER, User::where('username', 'new_farmer')->firstOrFail()->role);
    }

    public function test_admin_can_update_and_suspend_a_farmer(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $farmer = $this->user('farmer_a');

        $this->actingAs($admin)->put(route('admin.users.update', $farmer), [
            'name' => 'اسم محدث',
            'username' => 'farmer_updated',
            'email' => 'farmer.updated@gmail.com',
            'role' => 'farmer',
        ])->assertRedirect(route('admin.users.index'));

        $farmer->refresh();
        $this->assertSame('farmer_updated', $farmer->username);
        $this->assertFalse($farmer->is_active, 'إلغاء تحديد خانة التفعيل يوقف الحساب');

        $this->actingAs($admin)->post(route('admin.users.toggle-active', $farmer));
        $this->assertTrue($farmer->fresh()->is_active);
    }

    public function test_admin_cannot_demote_or_suspend_himself(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'role' => 'farmer',
        ])->assertRedirect();

        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);

        $this->actingAs($admin)->post(route('admin.users.toggle-active', $admin))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());
    }

    public function test_deleting_a_farmer_removes_his_data(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $farmer = $this->user('farmer_a');

        $crop = Crop::create(['user_id' => $farmer->id, 'name' => 'قمح']);
        Season::create([
            'user_id' => $farmer->id,
            'name' => 'موسم المزارع',
            'crop_id' => $crop->id,
            'field_id' => Field::create(['user_id' => $farmer->id, 'name' => 'أرض', 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $farmer))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull(User::find($farmer->id));
        $this->assertSame(0, Season::ownedBy($farmer->id)->count());
        $this->assertSame(0, Crop::ownedBy($farmer->id)->count());
    }

    public function test_admin_can_view_a_farmer_profile_with_his_seasons(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $farmer = $this->user('farmer_a');

        $crop = Crop::create(['user_id' => $farmer->id, 'name' => 'قمح']);
        Season::create([
            'user_id' => $farmer->id,
            'name' => 'موسم المزارع',
            'type' => 'summer',
            'crop_id' => $crop->id,
            'field_id' => Field::create(['user_id' => $farmer->id, 'name' => 'أرض', 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.users.show', $farmer))
            ->assertOk()
            ->assertSee('موسم المزارع')
            ->assertSee('صيفي', false);
    }
}
