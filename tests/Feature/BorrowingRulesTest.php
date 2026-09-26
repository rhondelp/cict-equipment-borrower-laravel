<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rules the landing page and the terms both state, enforced on the server.
 */
class BorrowingRulesTest extends TestCase
{
    use RefreshDatabase;

    private function equipment(int $quantity = 10): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Projector (Epson)', 'description' => 'd',
            'quantity' => $quantity, 'available_quantity' => $quantity, 'status' => 'Available',
        ]);
    }

    private function loan(User $user, Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $user->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(10)->toDateString(),
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
            'quantity' => 1,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    /* ---- Overdue items pause borrowing ----------------------------------- */

    public function test_a_borrower_with_an_overdue_item_cannot_request_more(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $this->loan($borrower, $equipment);

        $this->actingAs($borrower)->from('/borrower/dashboard')
            ->post('/borrower/request', ['equipment_id' => $equipment->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('item_requests', 0);
        $this->assertStringContainsString('overdue', session('errors')->first('quantity'));
        $this->assertStringContainsString('Projector (Epson)', session('errors')->first('quantity'));
    }

    /** The stored status lags until the nightly sweep; the due date decides. */
    public function test_the_block_uses_the_due_date_not_the_stored_status(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $this->loan($borrower, $equipment, ['status' => 'Borrowed']); // late, sweep not yet run

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $equipment->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');
    }

    public function test_a_loan_due_today_is_not_overdue_yet(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $this->loan($borrower, $equipment, ['return_date' => Carbon::today()->toDateString()]);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $equipment->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('item_requests', 1);
    }

    public function test_returned_and_voided_late_loans_do_not_block(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $this->loan($borrower, $equipment, ['status' => 'Returned']);
        $this->loan($borrower, $equipment, ['voided_at' => now(), 'void_reason' => 'Entered twice']);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $equipment->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('item_requests', 1);
    }

    public function test_someone_elses_overdue_loan_does_not_block_you(): void
    {
        $other = User::factory()->create(['user_type' => 'Student']);
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $this->loan($other, $equipment);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $equipment->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();
    }

    /* ---- Default loan period --------------------------------------------- */

    public function test_an_approved_request_is_due_after_the_configured_loan_period(): void
    {
        config(['office.loan_days' => 10]);
        Carbon::setTestNow(Carbon::parse('2026-09-28 09:00', 'Asia/Manila'));

        $admin = User::factory()->create(['user_type' => 'Admin']);
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $equipment = $this->equipment();
        $request = ItemRequest::create([
            'user_id' => $borrower->id, 'equipment_id' => $equipment->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.request.approve'), ['id' => $request->id]);

        $loan = BorrowTransaction::where('user_id', $borrower->id)->firstOrFail();
        $this->assertSame('2026-10-08', $loan->return_date->toDateString());

        Carbon::setTestNow();
    }
}
