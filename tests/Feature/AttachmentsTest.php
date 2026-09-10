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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * إرفاق صور الفواتير وسندات القبض.
 */
class AttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Season $season;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::create([
            'name' => 'مزارع',
            'username' => 'farmer_one',
            'email' => 'farmer.one@gmail.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->season = Season::create([
            'user_id' => $this->user->id,
            'name' => 'موسم القمح',
            'type' => 'winter',
            'crop_id' => Crop::create(['user_id' => $this->user->id, 'name' => 'قمح'])->id,
            'field_id' => Field::create(['user_id' => $this->user->id, 'name' => 'أرض', 'ownership_type' => 'owned'])->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    protected function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'season_id' => $this->season->id,
            'category_name' => 'بذار',
            'amount' => '150',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-02-01',
            'notes' => 'فاتورة بذار',
        ], $overrides);
    }

    public function test_expense_receipt_image_is_uploaded_and_stored(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('invoice.jpg'),
        ]))->assertRedirect(route('expenses.index'));

        $expense = Expense::firstOrFail();

        $this->assertNotNull($expense->receipt_image);
        $this->assertStringStartsWith('receipts/expenses/', $expense->receipt_image);
        Storage::disk('public')->assertExists($expense->receipt_image);
        $this->assertTrue($expense->hasReceipt());
        $this->assertFalse($expense->receiptIsPdf());
    }

    public function test_expense_accepts_pdf_documents(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->create('invoice.pdf', 200, 'application/pdf'),
        ]))->assertRedirect();

        $expense = Expense::firstOrFail();

        $this->assertTrue($expense->receiptIsPdf());
        Storage::disk('public')->assertExists($expense->receipt_image);
    }

    public function test_oversized_or_unsupported_files_are_rejected(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->create('virus.exe', 100),
        ]))->assertSessionHasErrors('receipt_image');

        $maxKb = (int) config('agri.attachments.max_size_kb');

        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->create('huge.jpg', $maxKb + 512),
        ]))->assertSessionHasErrors('receipt_image');

        $this->assertSame(0, Expense::count());
    }

    public function test_updating_an_expense_replaces_the_old_attachment(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('first.jpg'),
        ]));

        $expense = Expense::firstOrFail();
        $oldPath = $expense->receipt_image;

        $this->actingAs($this->user)->put(route('expenses.update', $expense), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('second.jpg'),
        ]))->assertRedirect(route('expenses.index'));

        $expense->refresh();

        $this->assertNotSame($oldPath, $expense->receipt_image);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($expense->receipt_image);
    }

    public function test_attachment_can_be_removed_from_an_expense(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('first.jpg'),
        ]));

        $expense = Expense::firstOrFail();
        $path = $expense->receipt_image;

        $this->actingAs($this->user)->put(route('expenses.update', $expense), $this->expensePayload([
            'remove_receipt' => '1',
        ]))->assertRedirect(route('expenses.index'));

        $expense->refresh();

        $this->assertNull($expense->receipt_image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_an_expense_deletes_its_attachment(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('first.jpg'),
        ]));

        $expense = Expense::firstOrFail();
        $path = $expense->receipt_image;

        $this->actingAs($this->user)->delete(route('expenses.destroy', $expense))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, Expense::count());
    }

    public function test_expense_thumbnail_is_rendered_in_the_list(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload([
            'receipt_image' => UploadedFile::fake()->image('invoice.jpg'),
        ]));

        $expense = Expense::firstOrFail();

        $this->actingAs($this->user)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee($expense->receiptUrl(), false)
            ->assertSee('data-attachment-url', false);
    }

    public function test_buyer_payment_receipt_is_uploaded_and_displayed(): void
    {
        Sale::create([
            'user_id' => $this->user->id,
            'season_id' => $this->season->id,
            'buyer_name' => 'أبو أحمد',
            'quantity' => 10,
            'unit' => 'طن',
            'unit_price' => 100,
            'total_price' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'date' => '2026-06-01',
        ]);

        $this->actingAs($this->user)->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '250',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
            'receipt_image' => UploadedFile::fake()->image('bank-transfer.png'),
        ])->assertRedirect(route('buyer-payments.index'));

        $payment = BuyerPayment::firstOrFail();

        $this->assertStringStartsWith('receipts/payments/', $payment->receipt_image);
        Storage::disk('public')->assertExists($payment->receipt_image);

        $this->actingAs($this->user)->get(route('buyer-payments.index'))
            ->assertOk()
            ->assertSee($payment->receiptUrl(), false);

        $this->actingAs($this->user)->get(route('buyer-payments.receipt', $payment))
            ->assertOk()
            ->assertSee($payment->receiptUrl(), false);
    }

    public function test_deleting_a_payment_deletes_its_attachment(): void
    {
        $this->actingAs($this->user)->post(route('buyer-payments.store'), [
            'buyer_name' => 'أبو أحمد',
            'amount' => '250',
            'currency' => 'USD',
            'exchange_rate' => '1',
            'date' => '2026-07-01',
            'receipt_image' => UploadedFile::fake()->image('bank-transfer.png'),
        ]);

        $payment = BuyerPayment::firstOrFail();
        $path = $payment->receipt_image;

        $this->actingAs($this->user)->delete(route('buyer-payments.destroy', $payment));

        Storage::disk('public')->assertMissing($path);
    }

    public function test_records_without_attachments_still_work(): void
    {
        ExpenseCategory::create(['user_id' => $this->user->id, 'name' => 'بذار']);

        $this->actingAs($this->user)->post(route('expenses.store'), $this->expensePayload())
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::firstOrFail();

        $this->assertNull($expense->receipt_image);
        $this->assertFalse($expense->hasReceipt());
        $this->assertNull($expense->receiptUrl());

        $this->actingAs($this->user)->get(route('expenses.index'))->assertOk();
    }
}
