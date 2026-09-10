<?php

namespace Tests\Feature;

use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Field;
use App\Models\Sale;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * عزل البيانات: كل مزارع يرى بياناته فقط، ومدير النظام يرى كل شيء.
 */
class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $username, string $role = User::ROLE_FARMER): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'email' => $username.'@gmail.com',
            'password' => Hash::make('secret123'),
            'role' => $role,
        ]);
    }

    /**
     * إنشاء بيانات كاملة (محصول، أرض، موسم، مصروف، بيع، سند) لمستخدم معيّن.
     *
     * @return array{season: Season, sale: Sale, expense: Expense}
     */
    protected function dataFor(User $user, string $label): array
    {
        $crop = Crop::create(['user_id' => $user->id, 'name' => 'قمح '.$label]);
        $field = Field::create(['user_id' => $user->id, 'name' => 'أرض '.$label, 'area_dunums' => 10, 'ownership_type' => 'owned']);

        $season = Season::create([
            'user_id' => $user->id,
            'name' => 'موسم '.$label,
            'type' => 'winter',
            'crop_id' => $crop->id,
            'field_id' => $field->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $category = ExpenseCategory::create(['user_id' => $user->id, 'name' => 'بذار '.$label]);

        $expense = Expense::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-02-01',
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'buyer_name' => 'تاجر '.$label,
            'quantity' => 10,
            'unit' => 'طن',
            'unit_price' => 100,
            'total_price' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-06-01',
        ]);

        BuyerPayment::create([
            'user_id' => $user->id,
            'buyer_name' => 'تاجر '.$label,
            'sale_id' => $sale->id,
            'season_id' => $season->id,
            'amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-07-01',
            'receipt_number' => 'REC-'.$label,
        ]);

        return compact('season', 'sale', 'expense');
    }

    public function test_farmer_sees_only_his_own_records(): void
    {
        $farmerA = $this->user('farmer_a');
        $farmerB = $this->user('farmer_b');

        $this->dataFor($farmerA, 'A');
        $this->dataFor($farmerB, 'B');

        $this->actingAs($farmerA);

        $this->assertSame(1, Season::count());
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, Expense::count());
        $this->assertSame(1, Crop::count());
        $this->assertSame(1, Field::count());
        $this->assertSame(1, BuyerPayment::count());
        $this->assertSame($farmerA->id, Season::first()->user_id);
    }

    public function test_admin_sees_all_records(): void
    {
        $admin = $this->user('admin', User::ROLE_ADMIN);
        $this->dataFor($this->user('farmer_a'), 'A');
        $this->dataFor($this->user('farmer_b'), 'B');

        $this->actingAs($admin);

        $this->assertSame(2, Season::count());
        $this->assertSame(2, Sale::count());
        $this->assertSame(2, BuyerPayment::count());
    }

    public function test_pages_do_not_leak_another_farmers_data(): void
    {
        $farmerA = $this->user('farmer_a');
        $farmerB = $this->user('farmer_b');

        $this->dataFor($farmerA, 'A');
        $this->dataFor($farmerB, 'B');

        $this->actingAs($farmerA)->get(route('seasons.index'))
            ->assertOk()
            ->assertSee('موسم A')
            ->assertDontSee('موسم B');

        $this->actingAs($farmerA)->get(route('sales.index'))
            ->assertOk()
            ->assertSee('تاجر A')
            ->assertDontSee('تاجر B');

        $this->actingAs($farmerA)->get(route('buyer-payments.index'))
            ->assertOk()
            ->assertSee('REC-A')
            ->assertDontSee('REC-B');
    }

    public function test_farmer_cannot_open_another_farmers_records(): void
    {
        $farmerA = $this->user('farmer_a');
        $dataB = $this->dataFor($this->user('farmer_b'), 'B');

        $this->actingAs($farmerA)->get(route('seasons.show', $dataB['season']))->assertNotFound();
        $this->actingAs($farmerA)->get(route('sales.edit', $dataB['sale']))->assertNotFound();
        $this->actingAs($farmerA)->get(route('expenses.edit', $dataB['expense']))->assertNotFound();
        $this->actingAs($farmerA)->delete(route('sales.destroy', $dataB['sale']))->assertNotFound();
    }

    public function test_farmer_cannot_attach_records_to_another_farmers_season(): void
    {
        $farmerA = $this->user('farmer_a');
        $dataB = $this->dataFor($this->user('farmer_b'), 'B');

        $this->actingAs($farmerA)->post(route('expenses.store'), [
            'season_id' => $dataB['season']->id,
            'category_name' => 'بذار',
            'amount' => '100',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-02-01',
        ])->assertSessionHas('error');

        $this->actingAs($farmerA)->post(route('sales.store'), [
            'season_id' => $dataB['season']->id,
            'buyer_name' => 'تاجر مزيف',
            'tons' => '5',
            'extra_kg' => '0',
            'unit_price' => '100',
            'paid_amount' => '0',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-06-01',
        ])->assertSessionHas('error');

        $this->assertSame(1, Expense::ownedBy($dataB['season']->user_id)->count());
        $this->assertSame(1, Sale::ownedBy($dataB['season']->user_id)->count());
    }

    public function test_farmer_cannot_pay_against_another_farmers_invoice(): void
    {
        $farmerA = $this->user('farmer_a');
        $dataB = $this->dataFor($this->user('farmer_b'), 'B');

        $this->actingAs($farmerA)->post(route('buyer-payments.store'), [
            'buyer_name' => 'تاجر B',
            'sale_id' => $dataB['sale']->id,
            'amount' => '100',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
        ])->assertSessionHas('error');

        $this->assertSame(1, BuyerPayment::ownedBy($dataB['sale']->user_id)->count());
    }

    public function test_new_records_are_stamped_with_the_current_user(): void
    {
        $farmer = $this->user('farmer_a');

        $this->actingAs($farmer)->post(route('crops.store'), ['name' => 'شعير'])->assertRedirect();

        $crop = Crop::ownedBy($farmer->id)->firstOrFail();
        $this->assertSame('شعير', $crop->name);
        $this->assertSame($farmer->id, $crop->user_id);
    }

    public function test_dashboard_totals_are_isolated_per_farmer(): void
    {
        $farmerA = $this->user('farmer_a');
        $this->dataFor($farmerA, 'A');
        $this->dataFor($this->user('farmer_b'), 'B');

        // إيرادات المزارع الواحد 1000 دولار فقط وليست 2000
        $this->actingAs($farmerA)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('$1,000.00')
            ->assertDontSee('$2,000.00');
    }
}
