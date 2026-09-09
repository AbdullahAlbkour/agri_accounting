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

class SeasonClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::create([
            'name' => 'مدير',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret123'),
        ]);
    }

    protected function season(string $status = 'active'): Season
    {
        return Season::create([
            'name' => 'موسم القمح',
            'crop_id' => Crop::create(['name' => 'قمح'])->id,
            'field_id' => Field::create(['name' => 'أرض الشمال', 'area_dunums' => 10, 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => $status,
        ]);
    }

    public function test_season_can_be_closed_and_reopened(): void
    {
        $user = $this->user();
        $season = $this->season();

        $this->actingAs($user)->post(route('seasons.close', $season))->assertRedirect();
        $this->assertSame('closed', $season->fresh()->status);
        $this->assertNotNull($season->fresh()->end_date);

        $this->actingAs($user)->post(route('seasons.reopen', $season))->assertRedirect();
        $this->assertSame('active', $season->fresh()->status);
    }

    public function test_expenses_cannot_be_created_for_a_closed_season(): void
    {
        $season = $this->season('closed');

        $this->actingAs($this->user())
            ->post(route('expenses.store'), [
                'season_id' => $season->id,
                'category_name' => 'بذار',
                'amount' => '100',
                'currency' => 'USD',
                'exchange_rate' => '1',
                'date' => '2026-02-01',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Expense::count());
    }

    public function test_sales_cannot_be_created_for_a_closed_season(): void
    {
        $season = $this->season('closed');

        $this->actingAs($this->user())
            ->post(route('sales.store'), [
                'season_id' => $season->id,
                'buyer_name' => 'أبو أحمد',
                'tons' => '5',
                'extra_kg' => '0',
                'unit_price' => '100',
                'paid_amount' => '0',
                'currency' => 'USD',
                'exchange_rate' => '1',
                'date' => '2026-06-01',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Sale::count());
    }

    public function test_existing_expense_of_a_closed_season_cannot_be_updated_or_deleted(): void
    {
        $season = $this->season();
        $category = ExpenseCategory::create(['name' => 'بذار']);

        $expense = Expense::create([
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-02-01',
        ]);

        $season->update(['status' => 'closed']);
        $user = $this->user();

        $this->actingAs($user)->get(route('expenses.edit', $expense))->assertSessionHas('error');

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'season_id' => $season->id,
            'category_name' => 'بذار',
            'amount' => '999',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-02-01',
        ])->assertSessionHas('error');

        $this->assertEquals(100, $expense->fresh()->amount);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense))->assertSessionHas('error');
        $this->assertSame(1, Expense::count());
    }

    public function test_closed_season_cannot_be_edited_or_deleted(): void
    {
        $user = $this->user();
        $season = $this->season('closed');

        $this->actingAs($user)->get(route('seasons.edit', $season))->assertSessionHas('error');
        $this->actingAs($user)->delete(route('seasons.destroy', $season))->assertSessionHas('error');

        $this->assertSame(1, Season::count());
    }

    public function test_archive_page_lists_closed_seasons_only(): void
    {
        $closed = $this->season('closed');
        $active = Season::create([
            'name' => 'موسم نشط',
            'crop_id' => $closed->crop_id,
            'field_id' => $closed->field_id,
            'start_date' => '2026-03-01',
            'status' => 'active',
        ]);

        $this->actingAs($this->user())
            ->get(route('seasons.archive'))
            ->assertOk()
            ->assertSee($closed->name)
            ->assertDontSee($active->name);
    }

    public function test_closed_season_stays_visible_in_reports(): void
    {
        $season = $this->season('closed');

        Sale::create([
            'season_id' => $season->id,
            'buyer_name' => 'أبو أحمد',
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

        $this->actingAs($this->user())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('1,000.00');
    }
}
