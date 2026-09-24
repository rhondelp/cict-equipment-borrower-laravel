<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The borrow transactions screen.
 *
 * Dates are the content here, and the order is the argument: an open-loans list
 * is worked worst-first. The derived status, the date range wording, the
 * check-in flow and the void-instead-of-delete rule are pinned by
 * RedesignRulesTest; what is pinned here is the queue order, the single sort
 * control, and the reminder actually being recorded rather than only sent.
 */
class LoansPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'Admin']);
    }

    private function borrower(string $name = 'Mia Santos'): User
    {
        return User::factory()->create(['user_type' => 'Student', 'name' => $name]);
    }

    private function equipment(string $name = 'Projector (Epson)', int $quantity = 20): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => 'Portable',
            'quantity' => $quantity,
            'available_quantity' => $quantity,
            'status' => 'Available',
        ]);
    }

    private function loan(User $user, Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $user->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(7)->toDateString(),
            'return_date' => Carbon::today()->addDays(5)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    private function html(): string
    {
        return $this->actingAs($this->admin())->get('/admin/transaction')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | Order
     ------------------------------------------------------------------ */

    /** Overdue, then due soon, then simply out. Settled loans come after. */
    public function test_the_list_is_ordered_worst_first(): void
    {
        $equipment = $this->equipment();

        $this->loan($this->borrower('Plain Out'), $equipment, [
            'return_date' => Carbon::today()->addDays(9)->toDateString(),
        ]);
        $this->loan($this->borrower('Settled'), $equipment, [
            'return_date' => Carbon::today()->subDays(1)->toDateString(),
            'status' => 'Returned',
        ]);
        $this->loan($this->borrower('Due Soon'), $equipment, [
            'return_date' => Carbon::today()->toDateString(),
        ]);
        $this->loan($this->borrower('Very Late'), $equipment, [
            'return_date' => Carbon::today()->subDays(6)->toDateString(),
        ]);

        $html = $this->html();
        $position = fn (string $name) => strpos($html, $name);

        $this->assertLessThan($position('Due Soon'), $position('Very Late'), 'Overdue is not first');
        $this->assertLessThan($position('Plain Out'), $position('Due Soon'), 'Due soon is not second');
        $this->assertLessThan($position('Settled'), $position('Plain Out'), 'A returned loan is above an open one');
    }

    /**
     * Ranked on the derived state, not the stored column. The nightly sweep
     * lags, so a loan still stored as "Borrowed" can be days late — ranking on
     * the stored status would bury it.
     */
    public function test_ordering_uses_the_derived_state_not_the_stored_status(): void
    {
        $equipment = $this->equipment();

        $this->loan($this->borrower('On Time'), $equipment, [
            'return_date' => Carbon::today()->addDays(4)->toDateString(),
        ]);
        $late = $this->loan($this->borrower('Silently Late'), $equipment, [
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        // The sweep has not run: still stored as Borrowed.
        $this->assertSame('Borrowed', $late->fresh()->status);

        $html = $this->html();
        $this->assertLessThan(strpos($html, 'On Time'), strpos($html, 'Silently Late'));
    }

    /** The default view is the open loans; closed ones sit behind a filter. */
    public function test_the_default_filter_is_open_loans(): void
    {
        $this->loan($this->borrower(), $this->equipment());

        $this->assertStringContainsString('data-active-chip="active"', $this->html());
    }

    /* ------------------------------------------------------------------
     | Chrome
     ------------------------------------------------------------------ */

    public function test_there_is_one_sort_control_with_row_keys(): void
    {
        $this->loan($this->borrower(), $this->equipment());
        $html = $this->html();

        $this->assertSame(1, substr_count($html, 'data-list-sort'), 'Not exactly one sort control');
        $this->assertStringContainsString('Most urgent first', $html);
        $this->assertStringContainsString('data-list-rows', $html);
        $this->assertStringContainsString('data-sort-urgency=', $html);
        $this->assertStringContainsString('data-sort-due=', $html);
        $this->assertStringContainsString('data-sort-person="Mia Santos"', $html);
    }

    /** Check in is a single icon button, not a wide label down the page. */
    public function test_check_in_is_an_icon_button(): void
    {
        $this->loan($this->borrower(), $this->equipment());
        $html = $this->html();

        $this->assertStringContainsString('data-checkin-trigger', $html);
        $this->assertStringContainsString('aria-label="Check in this loan"', $html);
        // A labelled wide button would put the words in the row.
        $this->assertStringNotContainsString('>Check in</button>', $html);
    }

    /** No control that types a status. */
    public function test_the_screen_offers_no_status_control(): void
    {
        $this->loan($this->borrower(), $this->equipment());
        $html = $this->html();

        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringNotContainsString('status-dropdown', $html);
    }

    /* ------------------------------------------------------------------
     | Reminders
     ------------------------------------------------------------------ */

    /** Sending records the reminder against the loan, with its time. */
    public function test_sending_a_reminder_records_it_against_the_loan(): void
    {
        Mail::fake();
        $borrower = $this->borrower();
        $loan = $this->loan($borrower, $this->equipment(), [
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->post('/send-email/'.$loan->id, ['type' => 'template'])
            ->assertOk()
            ->assertJsonStructure(['message', 'sent_at']);

        $this->assertDatabaseHas('notifications', [
            'borrow_transaction_id' => $loan->id,
            'user_id' => $borrower->id,
        ]);
        $this->assertNotNull(Notification::where('borrow_transaction_id', $loan->id)->first()->send_date);
    }

    /** A loan that has been chased says so; one that has not, on an overdue row, says that. */
    public function test_the_row_says_whether_the_loan_has_been_chased(): void
    {
        $borrower = $this->borrower();
        $loan = $this->loan($borrower, $this->equipment(), [
            'return_date' => Carbon::today()->subDays(4)->toDateString(),
        ]);

        $this->assertStringContainsString('Not chased yet', $this->html());

        Notification::create([
            'user_id' => $borrower->id,
            'borrow_transaction_id' => $loan->id,
            'message' => 'Please return the projector.',
            'notification_type' => 'Overdue notice',
            'send_date' => Carbon::today(),
        ]);

        $html = $this->html();
        $this->assertStringContainsString('data-last-reminder', $html);
        $this->assertStringContainsString('Reminded '.Carbon::today()->format('M j'), $html);
        $this->assertStringNotContainsString('Not chased yet', $html);
    }

    /** The recipient and the template are both shown before anything is sent. */
    public function test_the_email_dialog_names_the_template_and_the_recipient(): void
    {
        $borrower = $this->borrower();
        $this->loan($borrower, $this->equipment());
        $html = $this->html();

        $this->assertStringContainsString('Return reminder (written for this loan)', $html);
        $this->assertStringContainsString('data-email="'.$borrower->email.'"', $html);
        $this->assertStringContainsString('id="modalEmail"', $html);
    }

    /**
     * The dialog reads the loan off the row that opened it. A dialog filled
     * from "the current row" has no current row when the list is filtered,
     * which is how these render "undefined".
     */
    public function test_the_email_dialog_reads_the_loan_from_the_record(): void
    {
        $this->loan($this->borrower('Mia Santos'), $this->equipment('Laptop (Dell)'));
        $html = $this->html();

        $this->assertStringContainsString('data-email-summary', $html, 'The dialog has no slot for the record');
        $this->assertMatchesRegularExpression(
            '/data-email-trigger[^>]*data-summary="Laptop \(Dell\)[^"]*Mia Santos"/',
            $html,
            'The email trigger does not carry the loan it is about'
        );
    }

    /* ------------------------------------------------------------------
     | Deleting
     ------------------------------------------------------------------ */

    public function test_an_open_loan_cannot_be_deleted(): void
    {
        $loan = $this->loan($this->borrower(), $this->equipment());

        $this->actingAs($this->admin())->delete('/admin/transaction/'.$loan->id)->assertRedirect();
        $this->assertDatabaseHas('borrow_transactions', ['id' => $loan->id]);
    }

    public function test_a_closed_loan_with_a_return_log_cannot_be_deleted_either(): void
    {
        $admin = $this->admin();
        $loan = $this->loan($this->borrower(), $this->equipment(), ['status' => 'Returned']);
        ReturnLog::create([
            'borrow_transaction_id' => $loan->id,
            'return_date' => now(),
            'condition' => 'Good',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->delete('/admin/transaction/'.$loan->id)->assertRedirect();
        $this->assertDatabaseHas('borrow_transactions', ['id' => $loan->id]);
    }

    /** Void carries a reason and is offered in place of the hard delete. */
    public function test_voiding_is_offered_and_needs_a_reason(): void
    {
        $loan = $this->loan($this->borrower(), $this->equipment());

        $this->actingAs($this->admin())
            ->post('/admin/transaction/'.$loan->id.'/void', [])
            ->assertSessionHasErrors('void_reason');

        $this->assertNull($loan->fresh()->voided_at);
    }
}
