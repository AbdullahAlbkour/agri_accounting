<?php

namespace Tests\Feature;

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
 * فلترة سجل المصاريف والمبيعات حسب نوع الموسم العام (صيفي / شتوي / ...).
 */
class SeasonTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'مزارع',
            'username' => 'farmer_one',
            'email' => 'farmer.one@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->makeSeasonWithRecords('winter', 'الشتوي', 'قمح', 'تاجر الشتاء');
        $this->makeSeasonWithRecords('summer', 'الصيفي', 'قطن', 'تاجر الصيف');
    }

    protected function makeSeasonWithRecords(string $type, string $label, string $cropName, string $buyer): Season
    {
        $season = Season::create([
            'user_id' => $this->user->id,
            'name' => 'موسم '.$label,
            'type' => $type,
            'crop_id' => Crop::create(['user_id' => $this->user->id, 'name' => $cropName])->id,
            'field_id' => Field::create(['user_id' => $this->user->id, 'name' => 'أرض '.$label, 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        Expense::create([
            'user_id' => $this->user->id,
            'season_id' => $season->id,
            'expense_category_id' => ExpenseCategory::create(['user_id' => $this->user->id, 'name' => 'بذار '.$label])->id,
            'amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-02-01',
            'notes' => 'مصروف '.$label,
        ]);

        Sale::create([
            'user_id' => $this->user->id,
            'season_id' => $season->id,
            'buyer_name' => $buyer,
            'quantity' => 10,
            'unit' => 'طن',
            'unit_price' => 100,
            'total_price' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-06-01',
        ]);

        return $season;
    }

    public function test_expenses_can_be_filtered_by_season_type(): void
    {
        $this->actingAs($this->user)
            ->get(route('expenses.index', ['season_type' => 'winter']))
            ->assertOk()
            ->assertSee('مصروف الشتوي')
            ->assertDontSee('مصروف الصيفي');

        $this->actingAs($this->user)
            ->get(route('expenses.index', ['season_type' => 'summer']))
            ->assertOk()
            ->assertSee('مصروف الصيفي')
            ->assertDontSee('مصروف الشتوي');
    }

    public function test_sales_can_be_filtered_by_season_type(): void
    {
        // أسماء التجار تظهر أيضاً ضمن قائمة الفلترة، لذا نتحقق من صفوف الجدول نفسها
        $response = $this->actingAs($this->user)
            ->get(route('sales.index', ['season_type' => 'summer']))
            ->assertOk()
            ->assertSee('تاجر الصيف');

        $sales = $response->viewData('sales');

        $this->assertCount(1, $sales);
        $this->assertSame('تاجر الصيف', $sales->first()->buyer_name);
    }

    public function test_unfiltered_lists_show_every_season_type(): void
    {
        $this->actingAs($this->user)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('مصروف الشتوي')
            ->assertSee('مصروف الصيفي');

        $sales = $this->actingAs($this->user)->get(route('sales.index'))
            ->assertOk()
            ->viewData('sales');

        $this->assertCount(2, $sales);
    }

    public function test_seasons_index_and_archive_can_be_filtered_by_type(): void
    {
        $this->actingAs($this->user)->get(route('seasons.index', ['type' => 'winter']))
            ->assertOk()
            ->assertSee('موسم الشتوي')
            ->assertDontSee('موسم الصيفي');

        Season::where('type', 'winter')->update(['status' => 'closed']);
        Season::where('type', 'summer')->update(['status' => 'closed']);

        $this->actingAs($this->user)->get(route('seasons.archive', ['type' => 'summer']))
            ->assertOk()
            ->assertSee('موسم الصيفي')
            ->assertDontSee('موسم الشتوي');
    }

    public function test_season_type_is_saved_from_the_form(): void
    {
        $crop = Crop::create(['user_id' => $this->user->id, 'name' => 'ذرة']);
        $field = Field::create(['user_id' => $this->user->id, 'name' => 'أرض الذرة', 'ownership_type' => 'owned']);

        $this->actingAs($this->user)->post(route('seasons.store'), [
            'name' => 'موسم الذرة',
            'type' => 'autumn',
            'crop_id' => $crop->id,
            'field_id' => $field->id,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ])->assertRedirect(route('seasons.index'));

        $season = Season::where('name', 'موسم الذرة')->firstOrFail();

        $this->assertSame('autumn', $season->type);
        $this->assertSame('خريفي', $season->typeLabel());
    }

    public function test_invalid_season_type_is_rejected(): void
    {
        $crop = Crop::create(['user_id' => $this->user->id, 'name' => 'ذرة']);
        $field = Field::create(['user_id' => $this->user->id, 'name' => 'أرض الذرة', 'ownership_type' => 'owned']);

        $this->actingAs($this->user)->post(route('seasons.store'), [
            'name' => 'موسم خاطئ',
            'type' => 'not-a-season',
            'crop_id' => $crop->id,
            'field_id' => $field->id,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ])->assertSessionHasErrors('type');
    }
}
