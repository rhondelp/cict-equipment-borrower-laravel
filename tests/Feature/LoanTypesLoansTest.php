<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recording, editing, checking in and voiding loans by loan type.
 *
 * What is pinned: each item is recorded by its own loan type, read from the
 * equipment row and never from the request; a due date is required exactly
 * when something is coming back; time-limited items need a time that falls
 * after the borrow moment; an issue is never checked in, and voiding it puts
 * its units back. The clock is frozen at Monday 5 October 2026, 10:00.
 */
class LoanTypesLoansTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
        $this->admin = User::factory()->create(['user_type' => 'Admin']);
        $this->borrower = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function item(string $name, string $type = Equipment::LOAN_RETURNABLE, int $quantity = 10): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name, 'description' => 'd', 'loan_type' => $type,
            'quantity' => $quantity, 'available_quantity' => $quantity, 'status' => 'Available',
        ]);
    }

    /** @param  array<int, int>  $quantities  equipment id => units */
    private function record(array $quantities, array $overrides = [])
    {
        return $this->actingAs($this->admin)->from('/admin/transaction')->post('/admin/transaction', array_merge([
            'user_id' => $this->borrower->id,
            'equipment' => array_keys($quantities),
            'quantities' => $quantities,
            'borrow_date' => '2026-10-05',
            'return_date' => '2026-10-12',
            'purpose' => 'Thesis defense',
        ], $overrides));
    }

    private function loanFor(Equipment $equipment): BorrowTransaction
    {
        return BorrowTransaction::where('equipment_id', $equipment->id)->sole();
    }

    /* ---- Recording each type ------------------------------------------------ */

    public function test_a_returnable_item_is_lent_by_date(): void
    {
        $projector = $this->item('Projector');

        $this->record([$projector->id => 2])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Loan recorded — 2 units out, due back Oct 12.');

        $loan = $this->loanFor($projector);
        $this->assertSame('Borrowed', $loan->status);
        $this->assertFalse($loan->timed);
        $this->assertSame('2026-10-05 00:00:00', $loan->borrow_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-12 00:00:00', $loan->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(8, (int) $projector->fresh()->available_quantity);
    }

    public function test_a_time_limited_item_is_lent_to_the_minute(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T10:00', 'return_date' => '2026-10-05T11:30'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Loan recorded — 1 unit out, due back 11:30 AM.');

        $loan = $this->loanFor($clicker);
        $this->assertSame('Borrowed', $loan->status);
        $this->assertTrue($loan->timed);
        $this->assertSame('2026-10-05 10:00:00', $loan->borrow_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-05 11:30:00', $loan->return_date->format('Y-m-d H:i:s'));
        $this->assertSame('Oct 5, 10:00 AM → 11:30 AM · due at 11:30 AM', $loan->dateLine());
        $this->assertSame(9, (int) $clicker->fresh()->available_quantity);
    }

    public function test_a_non_returnable_item_needs_no_return_date_and_is_issued(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE, 20);

        $this->record([$cable->id => 5], ['return_date' => ''])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Issue recorded — 5 units issued, not expected back.');

        $issue = $this->loanFor($cable);
        $this->assertSame('Issued', $issue->status);
        $this->assertNull($issue->return_date);
        $this->assertFalse($issue->timed);
        $this->assertSame('Issued', $issue->derivedStatus());

        // Not out — counted as issued, and off the shelf either way.
        $cable->refresh();
        $this->assertSame(0, $cable->unitsOut());
        $this->assertSame(5, $cable->unitsIssued());
        $this->assertSame(15, $cable->derivedAvailableQuantity());
        $this->assertSame(15, (int) $cable->available_quantity);
    }

    public function test_a_return_date_sent_for_a_non_returnable_item_is_ignored(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);

        $this->record([$cable->id => 1], ['return_date' => '2026-10-12'])->assertSessionHasNoErrors();

        $this->assertNull($this->loanFor($cable)->return_date);
    }

    /** The status comes from the item, whatever the request says. */
    public function test_a_client_sent_status_decides_nothing(): void
    {
        $projector = $this->item('Projector');
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);

        $this->record([$projector->id => 1], ['status' => 'Issued'])->assertSessionHasNoErrors();
        $this->record([$cable->id => 1], ['status' => 'Borrowed', 'timed' => '1'])->assertSessionHasNoErrors();

        $this->assertSame('Borrowed', $this->loanFor($projector)->status);
        $this->assertSame('Issued', $this->loanFor($cable)->status);
        $this->assertFalse($this->loanFor($cable)->timed);
    }

    /* ---- Refusals ----------------------------------------------------------- */

    public function test_a_returnable_item_without_a_return_date_is_refused(): void
    {
        $projector = $this->item('Projector');

        $this->record([$projector->id => 1], ['return_date' => ''])
            ->assertSessionHasErrors('return_date');

        $this->assertStringContainsString('Projector', session('errors')->first('return_date'));
        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(10, (int) $projector->fresh()->available_quantity);
    }

    public function test_a_time_limited_item_without_a_time_is_refused(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T10:00', 'return_date' => '2026-10-05'])
            ->assertSessionHasErrors('return_date');
        $this->assertStringContainsString('Add the time', session('errors')->first('return_date'));

        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05', 'return_date' => '2026-10-05T11:00'])
            ->assertSessionHasErrors('borrow_date');

        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(10, (int) $clicker->fresh()->available_quantity);
    }

    public function test_a_time_limited_return_must_be_after_the_borrow_moment(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        foreach (['2026-10-05T10:00', '2026-10-05T09:30'] as $due) {
            $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T10:00', 'return_date' => $due])
                ->assertSessionHasErrors('return_date');
        }

        $this->assertDatabaseCount('borrow_transactions', 0);
    }

    /* ---- Mixed selections ---------------------------------------------------- */

    public function test_returnable_and_time_limited_together_share_one_datetime(): void
    {
        $projector = $this->item('Projector');
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $this->record([$projector->id => 1, $clicker->id => 1], [
            'borrow_date' => '2026-10-05T10:00', 'return_date' => '2026-10-05T15:00',
        ])->assertSessionHasNoErrors();

        $dated = $this->loanFor($projector);
        $this->assertFalse($dated->timed);
        $this->assertSame('2026-10-05 00:00:00', $dated->borrow_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-05 00:00:00', $dated->return_date->format('Y-m-d H:i:s'));

        $timed = $this->loanFor($clicker);
        $this->assertTrue($timed->timed);
        $this->assertSame('2026-10-05 15:00:00', $timed->return_date->format('Y-m-d H:i:s'));
    }

    public function test_a_mixed_selection_with_a_time_limited_item_still_needs_a_time(): void
    {
        $projector = $this->item('Projector');
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $this->record([$projector->id => 1, $clicker->id => 1])->assertSessionHasErrors('borrow_date');

        $this->assertDatabaseCount('borrow_transactions', 0);
    }

    public function test_returnable_and_non_returnable_together_need_the_date_for_the_returnable_one_only(): void
    {
        $projector = $this->item('Projector');
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);

        // Without a date the whole handover is refused: nothing is half-written.
        $this->record([$projector->id => 1, $cable->id => 3], ['return_date' => ''])
            ->assertSessionHasErrors('return_date');
        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(10, (int) $cable->fresh()->available_quantity);

        $this->record([$projector->id => 1, $cable->id => 3])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Loan recorded — 1 unit out, due back Oct 12; 3 units issued, not expected back.');

        $this->assertSame('Borrowed', $this->loanFor($projector)->status);
        $this->assertSame('2026-10-12', $this->loanFor($projector)->return_date->toDateString());
        $this->assertSame('Issued', $this->loanFor($cable)->status);
        $this->assertNull($this->loanFor($cable)->return_date);
    }

    /* ---- Check-in, void, delete --------------------------------------------- */

    public function test_an_issued_item_cannot_be_checked_in(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$cable->id => 2], ['return_date' => '']);
        $issue = $this->loanFor($cable);

        $this->actingAs($this->admin)->post('/admin/transaction/check-in', ['id' => $issue->id, 'condition' => 'Good'])
            ->assertSessionHasErrors('id');

        $this->assertSame('Issued', $issue->fresh()->status);
        $this->assertDatabaseCount('return_logs', 0);
        $this->assertSame(8, (int) $cable->fresh()->available_quantity);
    }

    public function test_voiding_an_issue_restores_its_stock(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$cable->id => 4], ['return_date' => '']);
        $issue = $this->loanFor($cable);

        $this->actingAs($this->admin)->post('/admin/transaction/'.$issue->id.'/void', ['void_reason' => 'Recorded against the wrong person'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Issue #'.$issue->id.' voided. It stays in the log, and its units are back in stock.');

        $cable->refresh();
        $this->assertSame(0, $cable->unitsIssued());
        $this->assertSame(10, (int) $cable->available_quantity);
        $this->assertSame(10, $cable->derivedAvailableQuantity());
    }

    public function test_voiding_a_timed_loan_restores_its_stock(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->record([$clicker->id => 2], ['borrow_date' => '2026-10-05T10:00', 'return_date' => '2026-10-05T11:00']);

        $this->actingAs($this->admin)->post('/admin/transaction/'.$this->loanFor($clicker)->id.'/void', ['void_reason' => 'Entered twice']);

        $this->assertSame(10, (int) $clicker->fresh()->available_quantity);
    }

    /** Deleting an issue would drop it from unitsIssued() and leave the shelf short. */
    public function test_an_issue_cannot_be_hard_deleted_until_voided(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$cable->id => 2], ['return_date' => '']);
        $issue = $this->loanFor($cable);

        $this->actingAs($this->admin)->delete('/admin/transaction/'.$issue->id)->assertSessionHas('error');
        $this->assertNotNull($issue->fresh());

        $this->actingAs($this->admin)->post('/admin/transaction/'.$issue->id.'/void', ['void_reason' => 'Entered twice']);
        $this->actingAs($this->admin)->delete('/admin/transaction/'.$issue->id)->assertSessionHas('success');
        $this->assertNull($issue->fresh());
        $this->assertSame(10, (int) $cable->fresh()->available_quantity);
    }

    /* ---- Editing ---------------------------------------------------------- */

    private function edit(BorrowTransaction $loan, array $overrides = [])
    {
        return $this->actingAs($this->admin)->from('/admin/transaction')->post('/admin/transaction/update', array_merge([
            'id' => $loan->id,
            'user_id' => $loan->user_id,
            'equipment_id' => $loan->equipment_id,
            'borrow_date' => $loan->borrow_date?->format($loan->timed ? 'Y-m-d\TH:i' : 'Y-m-d'),
            'return_date' => $loan->return_date?->format($loan->timed ? 'Y-m-d\TH:i' : 'Y-m-d') ?? '2026-10-12',
            'quantity' => $loan->quantity,
            'purpose' => $loan->purpose,
        ], $overrides));
    }

    public function test_an_issue_is_void_only(): void
    {
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$cable->id => 2], ['return_date' => '']);
        $issue = $this->loanFor($cable);

        $this->edit($issue, ['quantity' => 5, 'remarks' => 'Changed'])->assertSessionHasErrors('id');

        $this->assertSame(2, $issue->fresh()->quantity);
        $this->assertSame(8, (int) $cable->fresh()->available_quantity);
    }

    public function test_a_timed_loan_stays_timed_when_edited(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T10:00', 'return_date' => '2026-10-05T11:00']);
        $loan = $this->loanFor($clicker);

        $this->edit($loan, ['return_date' => '2026-10-05T12:15'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-05 12:15:00', $loan->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertTrue($loan->fresh()->timed);

        // A date alone, or a due time before the borrow moment, is refused.
        $this->edit($loan->fresh(), ['return_date' => '2026-10-06'])->assertSessionHasErrors('return_date');
        $this->edit($loan->fresh(), ['return_date' => '2026-10-05T09:00'])->assertSessionHasErrors('return_date');
        $this->assertSame('2026-10-05 12:15:00', $loan->fresh()->return_date->format('Y-m-d H:i:s'));
    }

    public function test_a_date_only_loan_stays_date_only_when_edited(): void
    {
        $projector = $this->item('Projector');
        $this->record([$projector->id => 1]);
        $loan = $this->loanFor($projector);

        $this->edit($loan, ['return_date' => '2026-10-14T16:45'])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-14 00:00:00', $loan->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertFalse($loan->fresh()->timed);
    }

    public function test_a_loan_cannot_move_to_an_item_of_another_loan_type(): void
    {
        $projector = $this->item('Projector');
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $spare = $this->item('Spare projector');
        $this->record([$projector->id => 1]);
        $loan = $this->loanFor($projector);

        foreach ([$clicker, $cable] as $other) {
            $this->edit($loan, ['equipment_id' => $other->id])->assertSessionHasErrors('equipment_id');
            $this->assertSame($projector->id, $loan->fresh()->equipment_id);
            $this->assertSame(10, (int) $other->fresh()->available_quantity);
        }

        $this->edit($loan, ['equipment_id' => $spare->id])->assertSessionHasNoErrors();
        $this->assertSame($spare->id, $loan->fresh()->equipment_id);
    }

    /* ---- The list --------------------------------------------------------- */

    public function test_the_list_shows_issues_without_check_in_and_timed_loans_with_times(): void
    {
        $projector = $this->item('Projector');
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$projector->id => 1]);
        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T08:00', 'return_date' => '2026-10-05T09:30']);
        $this->record([$cable->id => 2], ['return_date' => '']);

        $html = $this->actingAs($this->admin)->get('/admin/transaction')->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $row = fn (Equipment $e) => "//*[@id='loan-{$this->loanFor($e)->id}']";

        // The issue: a neutral Issued status, an "issued" chip key, no check-in, no edit, no email.
        $issueRow = $xpath->query($row($cable))->item(0);
        $this->assertSame('all issued', $issueRow->getAttribute('data-chip'));
        $this->assertStringContainsString('Issued Oct 5', $issueRow->textContent);
        $this->assertStringContainsString('Given out for good', $issueRow->textContent);
        $this->assertSame(0, $xpath->query($row($cable).'//*[@data-checkin-trigger]')->length);
        $this->assertSame(0, $xpath->query($row($cable).'//*[@data-loan-edit]')->length);
        $this->assertSame(0, $xpath->query($row($cable).'//*[@data-email-trigger]')->length);
        $this->assertStringContainsString("Can't delete — its units are counted as given out",
            $xpath->query($row($cable).'//*[@data-remove-trigger]')->item(0)->getAttribute('data-blocked'));
        $this->assertStringContainsString('data-list-chip="issued"', $html);

        // The timed loan: times in the date line, overdue by minutes, first in the queue.
        $timedRow = $xpath->query($row($clicker))->item(0);
        $this->assertStringContainsString('Oct 5, 8:00 AM → 9:30 AM', $timedRow->textContent);
        $this->assertStringContainsString('30 min late', $timedRow->textContent);
        $this->assertSame('all active overdue', $timedRow->getAttribute('data-chip'));
        $this->assertSame('1', $xpath->query($row($clicker).'//*[@data-loan-edit]')->item(0)->getAttribute('data-timed'));
        $this->assertSame('2026-10-05T09:30', $xpath->query($row($clicker).'//*[@data-loan-edit]')->item(0)->getAttribute('data-return'));

        $order = [];
        foreach ($xpath->query('//*[@data-list-row]') as $node) {
            $order[] = $node->getAttribute('id');
        }
        $this->assertSame('loan-'.$this->loanFor($clicker)->id, $order[0], 'The overdue timed loan leads the queue');
        $this->assertSame('loan-'.$this->loanFor($cable)->id, end($order), 'The issue sits with the settled records');

        $this->assertStringContainsString('Longest: 30 min late', $html);
    }

    public function test_the_new_loan_form_carries_each_items_loan_type_and_the_presets(): void
    {
        $this->item('Projector');
        $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);

        $html = $this->actingAs($this->admin)->get('/admin/transaction')->assertOk()->getContent();

        foreach (['returnable', 'time_limited', 'non_returnable'] as $type) {
            $this->assertMatchesRegularExpression('/class="[^"]*equipment-checkbox[^"]*"[^>]*data-loan-type="'.$type.'"/s', $html);
        }
        foreach (['30' => '+30 min', '60' => '+1 hour', '120' => '+2 hours'] as $minutes => $label) {
            $this->assertMatchesRegularExpression('/data-loan-preset="'.$minutes.'"[^>]*>\s*'.preg_quote($label, '/').'/', $html);
        }
        $this->assertStringContainsString('Time-Limited · due back at a set time', $html);
        $this->assertStringContainsString('Non-Returnable · given out for good', $html);
        $this->assertMatchesRegularExpression('/<option value="\d+"[^>]*data-loan-type="non_returnable"/', $html);
    }

    /** Issued and timed rows reach screens this prompt did not touch; they must still render. */
    public function test_other_screens_still_render_with_issued_and_timed_rows(): void
    {
        $clicker = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);
        $cable = $this->item('Patch cable', Equipment::LOAN_NON_RETURNABLE);
        $this->record([$clicker->id => 1], ['borrow_date' => '2026-10-05T08:00', 'return_date' => '2026-10-05T09:30']);
        $this->record([$cable->id => 2], ['return_date' => '']);

        foreach (['/admin/dashboard', '/admin/equipment', '/admin/users', '/admin/request', '/admin/logs'] as $uri) {
            $this->actingAs($this->admin)->get($uri)->assertOk();
        }
        $this->actingAs($this->borrower)->get('/borrower/dashboard')->assertOk();
    }
}
