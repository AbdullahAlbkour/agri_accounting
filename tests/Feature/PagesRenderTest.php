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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * اختبار سريع للتأكد من أن جميع صفحات النظام تُفتح دون أخطاء.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Season $season;

    protected Sale $sale;

    protected Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'مدير',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret123'),
        ]);

        $crop = Crop::create(['name' => 'قمح']);
        $field = Field::create(['name' => 'أرض الشمال', 'area_dunums' => 20, 'ownership_type' => 'owned']);

        $this->season = Season::create([
            'name' => 'موسم القمح الشتوي',
            'crop_id' => $crop->id,
            'field_id' => $field->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->expense = Expense::create([
            'season_id' => $this->season->id,
            'expense_category_id' => ExpenseCategory::create(['name' => 'بذار'])->id,
            'amount' => 500,
            'currency' => 'TRY',
            'exchange_rate' => 30,
            'date' => '2026-02-01',
        ]);

        $this->sale = Sale::create([
            'season_id' => $this->season->id,
            'buyer_name' => 'أبو أحمد',
            'quantity' => 12.5,
            'unit' => 'طن',
            'unit_price' => 200,
            'total_price' => 2500,
            'paid_amount' => 1000,
            'remaining_amount' => 1500,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-06-01',
        ]);

        BuyerPayment::create([
            'buyer_name' => 'أبو أحمد',
            'sale_id' => $this->sale->id,
            'season_id' => $this->season->id,
            'amount' => 500,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-07-01',
            'receipt_number' => 'REC-00001',
        ]);
    }

    public static function pageProvider(): array
    {
        return [
            'لوحة التحكم' => ['dashboard'],
            'المواسم' => ['seasons.index'],
            'إنشاء موسم' => ['seasons.create'],
            'أرشيف المواسم' => ['seasons.archive'],
            'المصاريف' => ['expenses.index'],
            'إضافة مصروف' => ['expenses.create'],
            'المبيعات' => ['sales.index'],
            'تسجيل بيع' => ['sales.create'],
            'الأراضي' => ['fields.index'],
            'إضافة أرض' => ['fields.create'],
            'المحاصيل' => ['crops.index'],
            'إضافة محصول' => ['crops.create'],
            'سندات القبض' => ['buyer-payments.index'],
            'التقارير' => ['reports.index'],
            'الإعدادات' => ['settings.index'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_page_renders(string $routeName): void
    {
        $this->actingAs($this->user)->get(route($routeName))->assertOk();
    }

    public function test_detail_and_edit_pages_render(): void
    {
        $this->actingAs($this->user)->get(route('seasons.show', $this->season))->assertOk();
        $this->actingAs($this->user)->get(route('seasons.edit', $this->season))->assertOk();
        $this->actingAs($this->user)->get(route('sales.edit', $this->sale))->assertOk();
        $this->actingAs($this->user)->get(route('expenses.edit', $this->expense))->assertOk();
    }

    public function test_reports_with_filters_render(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', [
                'buyer_name' => 'أبو أحمد',
                'expense_category_id' => $this->expense->expense_category_id,
                'search_query' => 'قمح',
            ]))
            ->assertOk()
            ->assertSee('أبو أحمد');
    }

    public function test_excel_export_streams_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.export.excel', ['buyer_name' => 'أبو أحمد']));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('كشف حساب التاجر', $content);
        $this->assertStringContainsString('REC-00001', $content);
    }
}
