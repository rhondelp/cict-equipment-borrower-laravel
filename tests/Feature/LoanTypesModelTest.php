<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The foundation for loan types: returnable, time-limited and non-returnable
 * items, timed loans due at a time of day, and Issued hand-overs. Model logic
 * only — no screen offers any of this yet, so every existing screen has to
 * keep reading exactly as it did.
 */
class LoanTypesModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function equipment(int $quantity = 10, array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => 'HDMI cable', 'description' => 'd',
            'quantity' => $quantity, 'available_quantity' => $quantity, 'status' => 'Available',
        ], $overrides));
    }

    private function loan(Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);

        return BorrowTransaction::create(array_merge([
            'user_id' => $borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => '2026-09-10',
            'return_date' => '2026-09-17',
            'quantity' => 1,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    /* ---- Schema ------------------------------------------------------------ */

    public function test_the_migrations_add_loan_type_timed_and_the_issued_status(): void
    {
        $this->assertTrue(Schema::hasColumn('equipment', 'loan_type'));
        $this->assertTrue(Schema::hasColumn('borrow_transactions', 'timed'));

        // The column default, not the model's, fills a row written behind Eloquent's back.
        DB::table('equipment')->insert([
            'equipment_name' => 'Raw row', 'quantity' => 1, 'available_quantity' => 1, 'status' => 'Available',
        ]);
        $this->assertSame('returnable', DB::table('equipment')->where('equipment_name', 'Raw row')->value('loan_type'));

        $issued = $this->loan($this->equipment(), ['status' => 'Issued']);
        $this->assertSame('Issued', $issued->fresh()->status);
        $this->assertFalse($issued->fresh()->timed);
    }

    /* ---- Equipment loan types --------------------------------------------- */

    public function test_loan_types_have_labels_and_predicates(): void
    {
        $this->assertSame([
            'returnable' => 'Returnable',
            'time_limited' => 'Time-Limited',
            'non_returnable' => 'Non-Returnable',
        ], Equipment::LOAN_TYPES);

        $default = $this->equipment();
        $this->assertSame(Equipment::LOAN_RETURNABLE, $default->fresh()->loan_type);
        $this->assertTrue($default->isReturnable());
        $this->assertSame('Returnable', $default->loanTypeLabel());

        $timed = $this->equipment(5, ['loan_type' => Equipment::LOAN_TIME_LIMITED]);
        $this->assertTrue($timed->isTimeLimited());
        $this->assertFalse($timed->isReturnable());
        $this->assertSame('Time-Limited', $timed->loanTypeLabel());

        $given = $this->equipment(5, ['loan_type' => Equipment::LOAN_NON_RETURNABLE]);
        $this->assertTrue($given->isNonReturnable());
        $this->assertSame('Non-Returnable', $given->loanTypeLabel());
    }

    /* ---- Due dates ---------------------------------------------------------- */

    public function test_a_date_only_loan_due_today_is_not_overdue_until_tomorrow(): void
    {
        $loan = $this->loan($this->equipment(), ['borrow_date' => '2026-09-10', 'return_date' => '2026-09-17']);

        Carbon::setTestNow('2026-09-17 08:00:00');
        $this->assertFalse($loan->isOverdue());
        $this->assertSame('due today', $loan->timingLabel());
        $this->assertSame('2026-09-17 23:59:59', $loan->dueAt()->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-09-17 23:59:59');
        $this->assertFalse($loan->isOverdue());
        $this->assertSame('Out', $loan->derivedStatus());

        Carbon::setTestNow('2026-09-18 00:00:01');
        $this->assertTrue($loan->isOverdue());
        $this->assertSame('Overdue', $loan->derivedStatus());
        $this->assertSame(1, $loan->daysLate());
        $this->assertSame('1 day late', $loan->timingLabel());
    }

    public function test_a_timed_loan_due_an_hour_ago_is_overdue(): void
    {
        Carbon::setTestNow('2026-10-05 16:30:00');
        $loan = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05 14:30:00',
            'return_date' => '2026-10-05 15:30:00',
            'timed' => true,
        ]);

        $this->assertTrue($loan->isOverdue());
        $this->assertSame('Overdue', $loan->derivedStatus());
        $this->assertSame('1 hr late', $loan->timingLabel());
        $this->assertSame(0, $loan->daysLate());
        $this->assertSame('Oct 5, 2:30 PM → 3:30 PM', $loan->dateRangeLabel());
        $this->assertSame('Oct 5, 2:30 PM → 3:30 PM · 1 hr late', $loan->dateLine());
    }

    public function test_a_timed_loan_reads_in_minutes_either_side_of_its_due_time(): void
    {
        $loan = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05 14:30:00',
            'return_date' => '2026-10-05 15:30:00',
            'timed' => true,
        ]);

        Carbon::setTestNow('2026-10-05 15:05:00');
        $this->assertFalse($loan->isOverdue());
        $this->assertSame('due in 25 min', $loan->timingLabel());
        $this->assertSame(0, $loan->daysUntilDue());

        Carbon::setTestNow('2026-10-05 15:40:00');
        $this->assertTrue($loan->isOverdue());
        $this->assertSame('10 min late', $loan->timingLabel());

        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->assertSame('due at 3:30 PM', $loan->timingLabel());

        Carbon::setTestNow('2026-10-07 16:00:00');
        $this->assertSame('2 days late', $loan->timingLabel());
        $this->assertSame(2, $loan->daysLate());
    }

    public function test_a_timed_loan_across_days_shows_both_dates(): void
    {
        $loan = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05 16:00:00',
            'return_date' => '2026-10-06 09:00:00',
            'timed' => true,
        ]);

        Carbon::setTestNow('2026-10-05 17:00:00');
        $this->assertSame('Oct 5, 4:00 PM → Oct 6, 9:00 AM', $loan->dateRangeLabel());
        $this->assertSame('due tomorrow, 9:00 AM', $loan->timingLabel());
    }

    /** return_logs.return_date is a DATE, so a same-day late return is judged by when it was logged. */
    public function test_a_timed_loan_returned_after_its_due_time_reads_late(): void
    {
        $loan = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05 14:30:00',
            'return_date' => '2026-10-05 15:30:00',
            'timed' => true,
            'status' => 'Returned',
        ]);

        Carbon::setTestNow('2026-10-05 15:45:00');
        ReturnLog::create(['borrow_transaction_id' => $loan->id, 'return_date' => now(), 'condition' => 'Good']);

        $this->assertSame('15 min late', $loan->fresh()->timingLabel());
        $this->assertFalse($loan->fresh()->isOverdue());
    }

    /* ---- Issued ------------------------------------------------------------- */

    public function test_issued_is_never_out_or_overdue(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $issued = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05',
            'return_date' => '2026-10-06',
            'status' => 'Issued',
        ]);

        $this->assertTrue($issued->isIssued());
        $this->assertFalse($issued->isOut());
        $this->assertFalse($issued->isOverdue());
        $this->assertSame(0, $issued->daysLate());
        $this->assertNull($issued->daysUntilDue());
        $this->assertSame('Issued', $issued->derivedStatus());
        $this->assertSame('neutral', $issued->statusTone());
        $this->assertSame('Issued Oct 5', $issued->dateRangeLabel());
        $this->assertSame('issued', $issued->timingLabel());
        $this->assertSame('Issued Oct 5', $issued->dateLine());

        // A timed issue with a past time is still not overdue.
        $timed = $this->loan($this->equipment(), [
            'borrow_date' => '2026-10-05 09:00:00', 'return_date' => '2026-10-05 10:00:00',
            'timed' => true, 'status' => 'Issued',
        ]);
        $this->assertFalse($timed->isOverdue());
        $this->assertSame('Issued', $timed->derivedStatus());
    }

    public function test_void_wins_over_issued(): void
    {
        $issued = $this->loan($this->equipment(), ['status' => 'Issued', 'voided_at' => now(), 'void_reason' => 'Entered twice']);

        $this->assertFalse($issued->isIssued());
        $this->assertSame('Void', $issued->derivedStatus());
        $this->assertSame('voided', $issued->timingLabel());
    }

    /* ---- Stock -------------------------------------------------------------- */

    public function test_units_issued_reduce_derived_availability_and_voiding_restores_it(): void
    {
        $equipment = $this->equipment(20);
        $this->loan($equipment, ['quantity' => 3, 'status' => 'Borrowed']);
        $issue = $this->loan($equipment, ['quantity' => 5, 'status' => 'Issued']);
        $this->loan($equipment, ['quantity' => 4, 'status' => 'Returned']);

        $this->assertSame(3, $equipment->unitsOut(), 'Issued is not out');
        $this->assertSame(5, $equipment->unitsIssued());
        $this->assertSame(12, $equipment->derivedAvailableQuantity());

        $issue->update(['voided_at' => now(), 'void_reason' => 'Recorded against the wrong item']);

        $this->assertSame(0, $equipment->unitsIssued());
        $this->assertSame(17, $equipment->derivedAvailableQuantity());
    }

    public function test_editing_an_item_recomputes_availability_without_its_issued_units(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);
        $equipment = $this->equipment(20);
        $this->loan($equipment, ['quantity' => 3, 'status' => 'Borrowed']);
        $this->loan($equipment, ['quantity' => 5, 'status' => 'Issued']);

        $payload = ['id' => $equipment->id, 'equipment_name' => 'HDMI cable', 'quantity' => 20];

        $this->actingAs($admin)->post('/admin/equipment/update', $payload)->assertSessionHasNoErrors();
        $this->assertSame(12, (int) $equipment->fresh()->available_quantity);

        // The total cannot drop below what is out plus what was issued.
        $this->actingAs($admin)->post('/admin/equipment/update', ['quantity' => 7] + $payload)
            ->assertSessionHasErrors('quantity');
        $this->assertSame(20, (int) $equipment->fresh()->quantity);

        // The index aggregate agrees with the per-row query.
        $listed = $this->actingAs($admin)->get('/admin/equipment')->viewData('equipment')
            ->firstWhere('id', $equipment->id);
        $this->assertSame(5, $listed->issuedNow());
        $this->assertSame(3, $listed->outNow());
    }

    /* ---- Existing rows ------------------------------------------------------ */

    public function test_existing_date_only_rows_render_exactly_as_before(): void
    {
        Carbon::setTestNow('2026-09-20 10:00:00');
        $equipment = $this->equipment();

        $late = $this->loan($equipment);
        $this->assertSame('Sep 10 → Sep 17 · 3 days late', $late->dateLine());
        $this->assertSame(3, $late->daysLate());
        $this->assertSame(-3, $late->daysUntilDue());

        $tomorrow = $this->loan($equipment, ['borrow_date' => '2026-09-18', 'return_date' => '2026-09-21']);
        $this->assertSame('Sep 18 → Sep 21 · due tomorrow', $tomorrow->dateLine());

        $later = $this->loan($equipment, ['borrow_date' => '2026-09-18', 'return_date' => '2026-09-25']);
        $this->assertSame('Sep 18 → Sep 25 · due in 5 days', $later->dateLine());

        $open = $this->loan($equipment, ['return_date' => null]);
        $this->assertSame('Sep 10 → open · no due date', $open->dateLine());

        $returned = $this->loan($equipment, ['status' => 'Returned']);
        ReturnLog::create(['borrow_transaction_id' => $returned->id, 'return_date' => '2026-09-19', 'condition' => 'Good']);
        $this->assertSame('Sep 10 → Sep 17 · 2 days late', $returned->fresh()->dateLine());

        // A row as the ALTER leaves it on MySQL: midnight, written behind the model.
        $id = DB::table('borrow_transactions')->insertGetId([
            'user_id' => $late->user_id, 'equipment_id' => $equipment->id,
            'borrow_date' => '2026-09-10 00:00:00', 'return_date' => '2026-09-17 00:00:00',
            'quantity' => 1, 'purpose' => 'Lab session', 'status' => 'Borrowed',
        ]);
        $migrated = BorrowTransaction::find($id);
        $this->assertFalse($migrated->timed);
        $this->assertSame('Sep 10 → Sep 17 · 3 days late', $migrated->dateLine());
    }

    public function test_booking_keys_group_date_only_loans_by_day_and_timed_loans_by_minute(): void
    {
        $equipment = $this->equipment();

        $dated = $this->loan($equipment);
        $this->assertSame('2026-09-10|2026-09-17|Lab session', $dated->bookingKey());

        $sessionA = $this->loan($equipment, [
            'borrow_date' => '2026-10-05 08:00:00', 'return_date' => '2026-10-05 10:00:00', 'timed' => true,
        ]);
        $sameSubmit = $this->loan($equipment, [
            'user_id' => $sessionA->user_id,
            'borrow_date' => '2026-10-05 08:00:42', 'return_date' => '2026-10-05 10:00:00', 'timed' => true,
        ]);
        $sessionB = $this->loan($equipment, [
            'user_id' => $sessionA->user_id,
            'borrow_date' => '2026-10-05 13:00:00', 'return_date' => '2026-10-05 15:00:00', 'timed' => true,
        ]);

        $this->assertSame($sessionA->bookingKey(), $sameSubmit->bookingKey());
        $this->assertNotSame($sessionA->bookingKey(), $sessionB->bookingKey());
        $this->assertSame([$sessionA->id, $sameSubmit->id], $sessionA->bookingLoans()->pluck('id')->all());
    }
}
