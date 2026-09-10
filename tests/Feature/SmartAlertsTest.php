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
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * التنبيهات الذكية: ديون التجار المتأخرة وسقف المصاريف.
 */
class SmartAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'agri.alerts.debt_threshold_usd' => 500,
            'agri.alerts.debt_idle_days' => 30,
            'agri.alerts.expense_ratio' => 0.8,
            'agri.alerts.season_idle_days' => 120,
        ]);

        $this->user = $this->makeUser('farmer_one');
    }

    protected function makeUser(string $username): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'email' => $username.'@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);
    }

    protected function season(User $user, string $name = 'موسم القمح', array $overrides = []): Season
    {
        return Season::create(array_merge([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'winter',
            'crop_id' => Crop::create(['user_id' => $user->id, 'name' => 'قمح '.$name])->id,
            'field_id' => Field::create(['user_id' => $user->id, 'name' => 'أرض '.$name, 'ownership_type' => 'owned'])->id,
            'start_date' => now()->subDays(60)->toDateString(),
            'status' => 'active',
        ], $overrides));
    }

    protected function sale(Season $season, string $buyer, float $total, float $paid, ?string $date = null): Sale
    {
        return Sale::create([
            'user_id' => $season->user_id,
            'season_id' => $season->id,
            'buyer_name' => $buyer,
            'quantity' => 10,
            'unit' => 'طن',
            'unit_price' => $total / 10,
            'total_price' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => $date ?? now()->subDays(10)->toDateString(),
        ]);
    }

    protected function expense(Season $season, float $amount): Expense
    {
        return Expense::create([
            'user_id' => $season->user_id,
            'season_id' => $season->id,
            'expense_category_id' => ExpenseCategory::create(['user_id' => $season->user_id, 'name' => 'بند '.uniqid()])->id,
            'amount' => $amount,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => now()->subDays(30)->toDateString(),
        ]);
    }

    protected function alerts(): Collection
    {
        $this->actingAs($this->user);

        return app(AlertService::class)->all();
    }

    public function test_no_alerts_when_everything_is_healthy(): void
    {
        $season = $this->season($this->user);
        $this->expense($season, 100);
        $this->sale($season, 'أبو أحمد', 1000, 1000);

        $this->assertCount(0, $this->alerts());
    }

    public function test_debt_above_threshold_raises_an_alert(): void
    {
        $season = $this->season($this->user);
        $this->sale($season, 'أبو أحمد', 2000, 400);

        $alerts = $this->alerts()->where('type', 'debt')->values();

        $this->assertCount(1, $alerts);
        $this->assertSame('danger', $alerts[0]['level']);
        $this->assertStringContainsString('أبو أحمد', $alerts[0]['title']);
        $this->assertStringContainsString('تجاوز الحد المسموح', $alerts[0]['message']);
        $this->assertEqualsWithDelta(1600, $alerts[0]['amount'], 0.01);
    }

    public function test_small_debt_below_threshold_is_ignored(): void
    {
        $season = $this->season($this->user);
        $this->sale($season, 'أبو أحمد', 1000, 900, now()->subDays(5)->toDateString());

        $this->assertCount(0, $this->alerts()->where('type', 'debt'));
    }

    public function test_old_unpaid_debt_raises_an_overdue_alert(): void
    {
        $season = $this->season($this->user);
        // دين صغير (تحت الحد) لكن مضى عليه أكثر من 30 يوماً دون دفعات
        $this->sale($season, 'أبو خالد', 1000, 900, now()->subDays(45)->toDateString());

        $alerts = $this->alerts()->where('type', 'debt')->values();

        $this->assertCount(1, $alerts);
        $this->assertSame('warning', $alerts[0]['level']);
        $this->assertStringContainsString('45 يوماً', $alerts[0]['message']);
    }

    public function test_recent_payment_clears_the_overdue_alert(): void
    {
        $season = $this->season($this->user);
        $sale = $this->sale($season, 'أبو خالد', 1000, 900, now()->subDays(45)->toDateString());

        BuyerPayment::create([
            'user_id' => $this->user->id,
            'buyer_name' => 'أبو خالد',
            'sale_id' => $sale->id,
            'season_id' => $season->id,
            'amount' => 20,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => now()->subDays(3)->toDateString(),
            'receipt_number' => 'REC-00001',
        ]);

        $this->assertCount(0, $this->alerts()->where('type', 'debt'));
    }

    public function test_expenses_over_the_configured_ratio_raise_an_alert(): void
    {
        $season = $this->season($this->user, 'موسم مكلف');
        $this->sale($season, 'أبو أحمد', 1000, 1000);
        $this->expense($season, 900); // 90% من الإيراد

        $alerts = $this->alerts()->where('type', 'expense_ratio')->values();

        $this->assertGreaterThanOrEqual(1, $alerts->count());
        $this->assertStringContainsString('موسم مكلف', $alerts[0]['title']);
        $this->assertStringContainsString('90', $alerts[0]['message']);
        $this->assertSame('warning', $alerts[0]['level']);
    }

    public function test_a_loss_making_season_is_flagged_as_danger(): void
    {
        $season = $this->season($this->user, 'موسم خاسر');
        $this->sale($season, 'أبو أحمد', 1000, 1000);
        $this->expense($season, 1400);

        $alert = $this->alerts()->firstWhere('type', 'expense_ratio');

        $this->assertSame('danger', $alert['level']);
        $this->assertStringContainsString('يعمل بخسارة', $alert['message']);
    }

    public function test_long_running_season_without_sales_is_flagged(): void
    {
        $season = $this->season($this->user, 'موسم راكد', ['start_date' => now()->subDays(200)->toDateString()]);
        $this->expense($season, 300);

        $alert = $this->alerts()->firstWhere('type', 'season_idle');

        $this->assertNotNull($alert);
        $this->assertStringContainsString('موسم راكد', $alert['title']);
        $this->assertStringContainsString('200 يوماً', $alert['message']);
    }

    public function test_alerts_page_and_navbar_badge_render(): void
    {
        $season = $this->season($this->user);
        $this->sale($season, 'أبو أحمد', 2000, 400);

        $this->actingAs($this->user)->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('التنبيهات والإشعارات الذكية')
            ->assertSee('أبو أحمد');

        // شارة الجرس في الشريط العلوي تظهر في كل الصفحات
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('التنبيهات الذكية')
            ->assertSee('fa-bell', false);
    }

    public function test_alerts_are_isolated_per_farmer(): void
    {
        $other = $this->makeUser('farmer_two');
        $otherSeason = $this->season($other, 'موسم الجار');
        $this->sale($otherSeason, 'تاجر الجار', 5000, 0);

        // المزارع الأول لا يرى تنبيهات غيره
        $this->assertCount(0, $this->alerts());

        $this->actingAs($other);
        $this->assertGreaterThanOrEqual(1, app(AlertService::class)->all()->count());

        $this->actingAs($this->user)->get(route('alerts.index'))
            ->assertOk()
            ->assertDontSee('تاجر الجار');
    }

    public function test_admin_sees_alerts_of_all_farmers(): void
    {
        $admin = User::create([
            'name' => 'مدير',
            'username' => 'admin',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret12345'),
            'role' => User::ROLE_ADMIN,
        ]);

        $season = $this->season($this->user);
        $this->sale($season, 'تاجر المزارع', 4000, 0);

        $this->actingAs($admin)->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('تاجر المزارع');
    }
}
