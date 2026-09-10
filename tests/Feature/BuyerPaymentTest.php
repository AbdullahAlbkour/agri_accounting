<?php

namespace Tests\Feature;

use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Sale;
use App\Models\Season;
use App\Models\User;
use App\Services\BuyerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BuyerPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::create([
            'name' => 'مدير',
            'username' => 'admin',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret123'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    protected function season(string $status = 'active'): Season
    {
        return Season::create([
            'name' => 'موسم القمح الشتوي',
            'crop_id' => Crop::create(['name' => 'قمح'])->id,
            'field_id' => Field::create(['name' => 'أرض الشمال', 'area_dunums' => 10, 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => $status,
        ]);
    }

    protected function sale(Season $season, array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'season_id' => $season->id,
            'buyer_name' => 'أبو أحمد',
            'quantity' => 10,
            'unit' => 'طن',
            'unit_price' => 100,
            'total_price' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-06-01',
        ], $overrides));
    }

    public function test_payment_is_stored_and_reduces_buyer_debt(): void
    {
        $season = $this->season();
        $this->sale($season);

        $accounts = app(BuyerAccountService::class);
        $this->assertEqualsWithDelta(600, $accounts->remainingForBuyerUSD('أبو أحمد'), 0.01);

        $this->actingAs($this->user())
            ->post(route('buyer-payments.store'), [
                'buyer_name' => 'أبو أحمد',
                'amount' => '250',
                'currency' => 'USD',
                'exchange_rate' => '1',
                'date' => '2026-07-01',
                'notes' => 'دفعة نقدية',
            ])
            ->assertRedirect(route('buyer-payments.index'));

        $payment = BuyerPayment::firstOrFail();
        $this->assertSame('REC-00001', $payment->receipt_number);
        $this->assertEqualsWithDelta(350, $accounts->remainingForBuyerUSD('أبو أحمد'), 0.01);
    }

    public function test_payment_in_foreign_currency_is_converted_to_usd(): void
    {
        $season = $this->season();
        $this->sale($season);

        $this->actingAs($this->user())->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '3000',
            'currency' => 'TRY',
            'exchange_rate' => '30',
            'date' => '2026-07-01',
        ]);

        // 3000 ليرة تركية / 30 = 100 دولار
        $this->assertEqualsWithDelta(100, BuyerPayment::firstOrFail()->amountUSD(), 0.01);
        $this->assertEqualsWithDelta(500, app(BuyerAccountService::class)->remainingForBuyerUSD('أبو أحمد'), 0.01);
    }

    public function test_payment_linked_to_a_sale_of_another_buyer_is_rejected(): void
    {
        $season = $this->season();
        $sale = $this->sale($season, ['buyer_name' => 'تاجر آخر']);

        $this->actingAs($this->user())
            ->post(route('buyer-payments.store'), [
                'buyer_name' => 'أبو أحمد',
                'sale_id' => $sale->id,
                'amount' => '100',
                'currency' => 'USD',
                'exchange_rate' => '1',
                'date' => '2026-07-01',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, BuyerPayment::count());
    }

    public function test_deleting_a_payment_restores_the_debt(): void
    {
        $season = $this->season();
        $this->sale($season);
        $user = $this->user();

        $this->actingAs($user)->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '600',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
        ]);

        $accounts = app(BuyerAccountService::class);
        $this->assertEqualsWithDelta(0, $accounts->remainingForBuyerUSD('أبو أحمد'), 0.01);

        $this->actingAs($user)->delete(route('buyer-payments.destroy', BuyerPayment::firstOrFail()));

        $this->assertEqualsWithDelta(600, $accounts->remainingForBuyerUSD('أبو أحمد'), 0.01);
    }

    public function test_dashboard_receivables_exclude_collected_payments(): void
    {
        $season = $this->season();
        $this->sale($season);
        $user = $this->user();

        $this->actingAs($user)->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '100',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('500.00');
    }

    public function test_receipt_page_renders(): void
    {
        $season = $this->season();
        $this->sale($season);
        $user = $this->user();

        $this->actingAs($user)->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '100',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
        ]);

        $this->actingAs($user)
            ->get(route('buyer-payments.receipt', BuyerPayment::firstOrFail()))
            ->assertOk()
            ->assertSee('سند قبض', false);
    }

    public function test_buyer_statement_page_renders_with_payments(): void
    {
        $season = $this->season();
        $this->sale($season);
        $user = $this->user();

        BuyerPayment::create([
            'buyer_name' => 'أبو أحمد',
            'amount' => 200,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-07-01',
            'receipt_number' => 'REC-00001',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', ['buyer_name' => 'أبو أحمد']))
            ->assertOk()
            ->assertSee('REC-00001')
            ->assertSee('400.00');
    }
}
