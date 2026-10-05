<?php

namespace Tests\Feature;

use App\Mail\ReturnNotification;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    /* ---- Loan types: approval ------------------------------------------- */

    private function typed(string $name, string $type, int $quantity = 10): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name, 'description' => 'd', 'loan_type' => $type,
            'quantity' => $quantity, 'available_quantity' => $quantity, 'status' => 'Available',
        ]);
    }

    /** Files a pending request for $equipment and has an admin approve it. */
    private function approve(Equipment $equipment, int $quantity = 1): BorrowTransaction
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $request = ItemRequest::create([
            'user_id' => $borrower->id, 'equipment_id' => $equipment->id, 'quantity' => $quantity,
            'status' => 'Pending', 'requested_date' => Carbon::today()->toDateString(), 'remarks' => 'Seminar',
        ]);

        $this->actingAs($admin)->post(route('admin.request.approve'), ['id' => $request->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('Approved', $request->fresh()->status);

        return BorrowTransaction::where('user_id', $borrower->id)->sole();
    }

    public function test_approving_a_returnable_request_lends_it_by_date(): void
    {
        Carbon::setTestNow('2026-10-05 10:20:00');
        $projector = $this->typed('Projector', Equipment::LOAN_RETURNABLE);

        $loan = $this->approve($projector, 2);

        $this->assertSame('Borrowed', $loan->status);
        $this->assertFalse($loan->timed);
        $this->assertSame('2026-10-05 00:00:00', $loan->borrow_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-12 00:00:00', $loan->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(8, (int) $projector->fresh()->available_quantity);
        $this->assertSame('Approved — 2 × Projector is now out with '.$loan->user->name.', due Oct 12.', session('success'));

        Carbon::setTestNow();
    }

    /** The clock starts at approval, for the period config sets. */
    public function test_approving_a_time_limited_request_starts_the_clock_at_approval(): void
    {
        config(['office.time_limited_minutes' => 45]);
        Carbon::setTestNow('2026-10-05 10:20:00');
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);

        $loan = $this->approve($clicker);

        $this->assertSame('Borrowed', $loan->status);
        $this->assertTrue($loan->timed);
        $this->assertSame('2026-10-05 10:20:00', $loan->borrow_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-05 11:05:00', $loan->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(9, (int) $clicker->fresh()->available_quantity);
        $this->assertStringEndsWith('due 11:05 AM.', session('success'));

        Carbon::setTestNow();
    }

    public function test_approving_a_non_returnable_request_issues_it(): void
    {
        Carbon::setTestNow('2026-10-05 10:20:00');
        $cable = $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE, 20);

        $issue = $this->approve($cable, 3);

        $this->assertSame('Issued', $issue->status);
        $this->assertNull($issue->return_date);
        $this->assertFalse($issue->timed);
        $this->assertSame('2026-10-05', $issue->borrow_date->toDateString());

        // Counted as issued, not out — and stored availability agrees with the derived figure.
        $cable->refresh();
        $this->assertSame(0, $cable->unitsOut());
        $this->assertSame(3, $cable->unitsIssued());
        $this->assertSame(17, $cable->derivedAvailableQuantity());
        $this->assertSame(17, (int) $cable->available_quantity);
        $this->assertStringEndsWith('issued to '.$issue->user->name.', not expected back.', session('success'));

        Carbon::setTestNow();
    }

    public function test_approving_still_refuses_a_short_shelf_for_every_type(): void
    {
        foreach ([Equipment::LOAN_TIME_LIMITED, Equipment::LOAN_NON_RETURNABLE] as $type) {
            $item = $this->typed('Short '.$type, $type, 1);
            $admin = User::factory()->create(['user_type' => 'Admin']);
            $request = ItemRequest::create([
                'user_id' => User::factory()->create(['user_type' => 'Student'])->id, 'equipment_id' => $item->id,
                'quantity' => 2, 'status' => 'Pending', 'requested_date' => Carbon::today()->toDateString(),
            ]);

            $this->actingAs($admin)->post(route('admin.request.approve'), ['id' => $request->id])
                ->assertSessionHasErrors('quantity');

            $this->assertSame('Pending', $request->fresh()->status);
            $this->assertSame(0, BorrowTransaction::where('equipment_id', $item->id)->count());
        }
    }

    /* ---- Loan types: the overdue block ---------------------------------- */

    public function test_a_late_timed_loan_blocks_new_requests(): void
    {
        Carbon::setTestNow('2026-10-05 14:00:00');
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->loan($borrower, $clicker, [
            'borrow_date' => '2026-10-05 12:00:00', 'return_date' => '2026-10-05 13:00:00', 'timed' => true,
        ]);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $this->equipment()->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertStringContainsString('Clicker, due Oct 5, 1:00 PM', session('errors')->first('quantity'));
        $this->assertDatabaseCount('item_requests', 0);

        Carbon::setTestNow();
    }

    public function test_a_timed_loan_inside_its_time_does_not_block(): void
    {
        Carbon::setTestNow('2026-10-05 12:30:00');
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->loan($borrower, $clicker, [
            'borrow_date' => '2026-10-05 12:00:00', 'return_date' => '2026-10-05 13:00:00', 'timed' => true,
        ]);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $this->equipment()->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();

        Carbon::setTestNow();
    }

    public function test_an_issued_item_never_blocks_new_requests(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);
        $cable = $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->loan($borrower, $cable, ['status' => 'Issued', 'return_date' => null, 'borrow_date' => Carbon::today()->subMonth()]);

        $this->actingAs($borrower)
            ->post('/borrower/request', ['equipment_id' => $this->equipment()->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('item_requests', 1);
    }

    /* ---- Loan types: the nightly sweep and reminders -------------------- */

    public function test_the_sweep_marks_overdue_by_due_moment_and_never_touches_an_issue(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-10-05 08:00:00');
        $item = $this->equipment();
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        $person = fn () => User::factory()->create(['user_type' => 'Student']);

        $datedYesterday = $this->loan($person(), $item, ['return_date' => '2026-10-04']);
        $datedToday = $this->loan($person(), $item, ['return_date' => '2026-10-05']);
        $timedEarlier = $this->loan($person(), $clicker, ['borrow_date' => '2026-10-05 06:00:00', 'return_date' => '2026-10-05 07:00:00', 'timed' => true]);
        $timedLater = $this->loan($person(), $clicker, ['borrow_date' => '2026-10-05 07:30:00', 'return_date' => '2026-10-05 09:00:00', 'timed' => true]);
        $issue = $this->loan($person(), $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE), [
            'status' => 'Issued', 'return_date' => null, 'borrow_date' => '2026-09-01',
        ]);

        $this->artisan('notifications:return')->expectsOutputToContain('return notifications sent')->assertSuccessful();

        $this->assertSame('Overdue', $datedYesterday->fresh()->status);
        $this->assertSame('Borrowed', $datedToday->fresh()->status);
        $this->assertSame('Overdue', $timedEarlier->fresh()->status);
        $this->assertSame('Borrowed', $timedLater->fresh()->status);
        $this->assertSame('Issued', $issue->fresh()->status);

        // Reminders go to the two loans due today and still out; a timed one names its time.
        Mail::assertSent(ReturnNotification::class, 2);
        Mail::assertSent(ReturnNotification::class, fn ($mail) => $mail->hasTo($timedLater->user->email)
            && str_contains($mail->details['body'], 'due back on Oct 5 at 9:00 AM'));
        Mail::assertSent(ReturnNotification::class, fn ($mail) => $mail->hasTo($datedToday->user->email)
            && str_contains($mail->details['body'], 'due back on Oct 5.'));
        Mail::assertNotSent(ReturnNotification::class, fn ($mail) => $mail->hasTo($issue->user->email));

        Carbon::setTestNow();
    }

    public function test_a_manual_reminder_is_refused_for_an_issue(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['user_type' => 'Admin']);
        $issue = $this->loan(User::factory()->create(['user_type' => 'Student']), $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE), [
            'status' => 'Issued', 'return_date' => null,
        ]);

        $this->actingAs($admin)->postJson('/send-email/'.$issue->id, ['type' => 'reminder'])->assertStatus(422);
        Mail::assertNothingSent();

        $this->actingAs($admin)->postJson('/send-email/'.$issue->id, ['type' => 'custom', 'message' => 'Thanks for collecting.'])->assertOk();
    }

    /* ---- Loan types: aggregates ----------------------------------------- */

    /** Out-now and overdue figures count isOut() loans and judge them by dueAt(). */
    public function test_admin_aggregates_count_a_late_timed_loan_and_ignore_an_issue(): void
    {
        Carbon::setTestNow('2026-10-05 14:00:00');
        $admin = User::factory()->create(['user_type' => 'Admin']);
        $borrower = User::factory()->create(['user_type' => 'Student', 'name' => 'Lia Reyes']);
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        $cable = $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->loan($borrower, $clicker, ['borrow_date' => '2026-10-05 12:00:00', 'return_date' => '2026-10-05 13:00:00', 'timed' => true]);
        $this->loan($borrower, $cable, ['status' => 'Issued', 'return_date' => null, 'quantity' => 5, 'borrow_date' => '2026-09-01']);
        ItemRequest::create([
            'user_id' => $borrower->id, 'equipment_id' => $clicker->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => '2026-10-05',
        ]);

        // Dashboard: one loan out, one overdue, worded in minutes.
        $dashboard = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->assertCount(1, $dashboard->viewData('openLoans'));
        $this->assertSame('1 loan overdue', $dashboard->viewData('attention')[0]['title']);
        $dashboard->assertSee('Longest: 1 hr late');

        // Users screen: the issue is not "held", the late timed loan is overdue.
        $row = $this->actingAs($admin)->get('/admin/users')->assertOk()->viewData('users')->firstWhere('id', $borrower->id);
        $this->assertSame(1, (int) $row->units_out);
        $this->assertSame(1, (int) $row->overdue_count);

        // Request queue: the standing beside the name says the same.
        $standing = $this->actingAs($admin)->get('/admin/request')->assertOk()->viewData('standing')->get($borrower->id);
        $this->assertSame(1, (int) $standing->units_out);
        $this->assertSame(1, (int) $standing->overdue_loans);

        Carbon::setTestNow();
    }

    public function test_the_overdue_scope_matches_is_overdue_row_by_row(): void
    {
        Carbon::setTestNow('2026-10-05 14:00:00');
        $user = User::factory()->create(['user_type' => 'Student']);
        $item = $this->equipment();
        $cases = [
            ['return_date' => '2026-10-04'],
            ['return_date' => '2026-10-05'],
            ['return_date' => '2026-10-05 13:59:00', 'timed' => true],
            ['return_date' => '2026-10-05 14:30:00', 'timed' => true],
            ['return_date' => '2026-10-04', 'status' => 'Returned'],
            ['return_date' => '2026-10-04', 'voided_at' => now(), 'void_reason' => 'Entered twice'],
            ['return_date' => null, 'status' => 'Issued'],
        ];
        foreach ($cases as $overrides) {
            $this->loan($user, $item, $overrides);
        }

        $byModel = BorrowTransaction::all()->filter->isOverdue()->pluck('id')->sort()->values()->all();
        $byScope = BorrowTransaction::overdue()->pluck('id')->sort()->values()->all();

        $this->assertSame($byModel, $byScope);
        $this->assertCount(2, $byScope);

        Carbon::setTestNow();
    }
}
