<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rules the admin and borrower screens were redesigned against, pinned as
 * behaviour rather than as markup wherever that was possible.
 *
 * These are the properties that are cheap to undo by accident — a status field
 * creeping back onto a form, a delete that stops checking what references the
 * row, a decline that goes through without a reason — and none of them are
 * covered by the other two suites.
 */
class RedesignRulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'Admin']);
    }

    private function borrower(): User
    {
        return User::factory()->create(['user_type' => 'Student']);
    }

    private function equipment(int $quantity = 5, ?int $available = null): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Projector (Epson)',
            'description' => 'Portable projector',
            'quantity' => $quantity,
            'available_quantity' => $available ?? $quantity,
            'status' => 'Available',
        ]);
    }

    private function loan(User $user, Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $user->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(7)->toDateString(),
            'return_date' => Carbon::today()->addDays(3)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    /* ---------------------------------------------------------------------
     | Rule: delete the DataTables chrome
     --------------------------------------------------------------------- */

    public function test_admin_pages_ship_no_datatables_chrome(): void
    {
        $admin = $this->admin();
        $this->equipment();

        foreach (['/admin/dashboard', '/admin/equipment', '/admin/users', '/admin/transaction', '/admin/request', '/admin/logs', '/admin/notifications'] as $uri) {
            $html = $this->actingAs($admin)->get($uri)->assertOk()->getContent();

            $this->assertStringNotContainsString('cdn.datatables.net', $html, "$uri still loads DataTables");
            $this->assertStringNotContainsString('Show _MENU_ entries', $html, "$uri still offers a page-length select");
            $this->assertStringNotContainsString('dataTables_paginate', $html, "$uri still paginates");
            // jQuery was loaded on every page for DataTables and nothing else.
            $this->assertStringNotContainsString('code.jquery.com', $html, "$uri still loads jQuery");
        }
    }

    /* ---------------------------------------------------------------------
     | Rule: status is derived, never a form field
     --------------------------------------------------------------------- */

    public function test_no_screen_offers_a_control_that_types_a_status(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment();
        $this->loan($this->borrower(), $equipment);

        $loans = $this->actingAs($admin)->get('/admin/transaction')->assertOk()->getContent();
        $this->assertStringNotContainsString('name="status"', $loans, 'The loans screen still posts a status');
        $this->assertStringNotContainsString('status-dropdown', $loans, 'The inline status dropdown is back');

        $stock = $this->actingAs($admin)->get('/admin/equipment')->assertOk()->getContent();
        $this->assertStringNotContainsString('name="status"', $stock, 'The equipment form still posts a status');
        $this->assertStringNotContainsString('name="available_quantity"', $stock, 'Availability is typeable again');
    }

    public function test_equipment_availability_is_derived_from_loans_not_from_the_form(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(10, 10);
        $this->loan($this->borrower(), $equipment, ['quantity' => 4]);

        // Even posting the old field names, availability comes out as total − out.
        $this->actingAs($admin)->post('/admin/equipment/update', [
            'id' => $equipment->id,
            'equipment_name' => 'Projector (Epson)',
            'description' => 'Portable projector',
            'quantity' => 10,
            'available_quantity' => 999,
            'status' => 'Available',
        ])->assertRedirect();

        $equipment->refresh();
        $this->assertSame(6, $equipment->available_quantity);
        $this->assertSame('Available', $equipment->status);
    }

    public function test_a_total_cannot_be_pushed_below_the_units_that_are_out(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(10, 6);
        $this->loan($this->borrower(), $equipment, ['quantity' => 4]);

        $this->actingAs($admin)->post('/admin/equipment/update', [
            'id' => $equipment->id,
            'equipment_name' => 'Projector (Epson)',
            'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(10, $equipment->fresh()->quantity);
    }

    public function test_overdue_is_read_off_the_due_date_not_off_the_stored_status(): void
    {
        $equipment = $this->equipment();
        // Still stored as Borrowed: the nightly sweep has not run.
        $loan = $this->loan($this->borrower(), $equipment, [
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        $this->assertSame('Borrowed', $loan->status);
        $this->assertSame('Overdue', $loan->derivedStatus());
        $this->assertSame(3, $loan->daysLate());
    }

    /* ---------------------------------------------------------------------
     | Rule: dates read "Sep 10 → Sep 17 · 3 days late"
     --------------------------------------------------------------------- */

    public function test_dates_are_shown_as_a_range_with_the_lateness_spelled_out(): void
    {
        $equipment = $this->equipment();
        $loan = $this->loan($this->borrower(), $equipment, [
            'borrow_date' => '2026-09-10',
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        $this->assertStringContainsString('Sep 10 → ', $loan->dateRangeLabel());
        $this->assertSame('3 days late', $loan->timingLabel());

        $html = $this->actingAs($this->admin())->get('/admin/transaction')->assertOk()->getContent();
        $this->assertStringContainsString('→', $html, 'The loans table is not showing a date range');
        $this->assertStringContainsString('3 days late', $html);
        // The raw ISO form of the borrow date must not be on the page.
        $this->assertStringNotContainsString('>2026-09-10<', $html);
    }

    /* ---------------------------------------------------------------------
     | Rule: reason text is mandatory on any decline
     --------------------------------------------------------------------- */

    public function test_a_decline_without_a_reason_is_rejected(): void
    {
        $admin = $this->admin();
        $request = ItemRequest::create([
            'user_id' => $this->borrower()->id,
            'equipment_id' => $this->equipment()->id,
            'quantity' => 1,
            'status' => 'Pending',
            'requested_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($admin)->post('/admin/request/decline', ['id' => $request->id])
            ->assertSessionHasErrors('reason');

        $this->assertSame('Pending', $request->fresh()->status);
    }

    public function test_a_decline_records_its_reason_and_the_borrower_sees_it(): void
    {
        $admin = $this->admin();
        $borrower = $this->borrower();
        $request = ItemRequest::create([
            'user_id' => $borrower->id,
            'equipment_id' => $this->equipment()->id,
            'quantity' => 1,
            'status' => 'Pending',
            'requested_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($admin)->post('/admin/request/decline', [
            'id' => $request->id,
            'reason' => 'A projector already went out with your laptop loan.',
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('Declined', $request->status);
        $this->assertSame('A projector already went out with your laptop loan.', $request->decision_reason);
        $this->assertSame($admin->id, $request->decided_by);
        $this->assertNotNull($request->decided_at);

        $this->actingAs($borrower)->get('/borrower/dashboard')
            ->assertOk()
            ->assertSee('A projector already went out with your laptop loan.', false);
    }

    /* ---------------------------------------------------------------------
     | Rule: destructive actions state the consequences, offer a safe default,
     |       and refuse the hard delete while history references the row
     --------------------------------------------------------------------- */

    public function test_equipment_with_history_cannot_be_deleted_but_can_be_retired(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(4, 2);
        $this->loan($this->borrower(), $equipment);

        $this->actingAs($admin)->delete('/admin/equipment/'.$equipment->id)->assertRedirect();
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id]);

        $this->actingAs($admin)->post('/admin/equipment/'.$equipment->id.'/retire')->assertRedirect();
        $equipment->refresh();
        $this->assertNotNull($equipment->retired_at);
        $this->assertSame('Unavailable', $equipment->status);
        // The loan it was carrying is untouched.
        $this->assertDatabaseCount('borrow_transactions', 1);
    }

    public function test_equipment_with_no_history_deletes_cleanly(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment();

        $this->actingAs($admin)->delete('/admin/equipment/'.$equipment->id)->assertRedirect();
        $this->assertDatabaseMissing('equipment', ['id' => $equipment->id]);
    }

    public function test_a_person_with_history_cannot_be_deleted_but_can_be_deactivated(): void
    {
        $admin = $this->admin();
        $borrower = $this->borrower();
        $this->loan($borrower, $this->equipment());

        $this->actingAs($admin)->delete('/admin/users/'.$borrower->id)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $borrower->id]);

        $this->actingAs($admin)->post('/admin/users/'.$borrower->id.'/deactivate')->assertRedirect();
        $this->assertNotNull($borrower->fresh()->deactivated_at);
    }

    /** Deactivating has to mean something, or the dialog is offering a no-op. */
    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $borrower = User::factory()->create([
            'user_type' => 'Student',
            'email' => 'deactivated@example.com',
            'deactivated_at' => now(),
        ]);

        $this->post('/login', ['email' => $borrower->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_open_loan_cannot_be_deleted_but_can_be_voided(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(5, 3);
        $loan = $this->loan($this->borrower(), $equipment);

        $this->actingAs($admin)->delete('/admin/transaction/'.$loan->id)->assertRedirect();
        $this->assertDatabaseHas('borrow_transactions', ['id' => $loan->id]);

        $this->actingAs($admin)->post('/admin/transaction/'.$loan->id.'/void', [
            'void_reason' => 'Recorded against the wrong borrower.',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertNotNull($loan->voided_at);
        // Voiding an open loan puts its units back on the shelf.
        $this->assertSame(5, $equipment->fresh()->available_quantity);
    }

    public function test_voiding_needs_a_reason(): void
    {
        $admin = $this->admin();
        $loan = $this->loan($this->borrower(), $this->equipment());

        $this->actingAs($admin)->post('/admin/transaction/'.$loan->id.'/void', [])
            ->assertSessionHasErrors('void_reason');

        $this->assertNull($loan->fresh()->voided_at);
    }

    public function test_a_returned_loan_is_part_of_the_return_history_and_cannot_be_deleted(): void
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

    /* ---------------------------------------------------------------------
     | Rule: check in the equipment, do not type a status
     --------------------------------------------------------------------- */

    public function test_checking_in_restores_stock_and_writes_a_log(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(5, 3);
        $loan = $this->loan($this->borrower(), $equipment);

        $this->actingAs($admin)->post('/admin/transaction/check-in', [
            'id' => $loan->id,
            'condition' => 'Good',
        ])->assertRedirect();

        $this->assertSame('Returned', $loan->fresh()->status);
        $this->assertSame(5, $equipment->fresh()->available_quantity);
        $this->assertDatabaseHas('return_logs', ['borrow_transaction_id' => $loan->id, 'condition' => 'Good']);
    }

    public function test_a_damaged_return_has_to_say_what_is_wrong(): void
    {
        $admin = $this->admin();
        $loan = $this->loan($this->borrower(), $this->equipment());

        $this->actingAs($admin)->post('/admin/transaction/check-in', [
            'id' => $loan->id,
            'condition' => 'Damaged',
        ])->assertSessionHasErrors('remarks');

        $this->assertSame('Borrowed', $loan->fresh()->status);
    }

    public function test_a_returned_loan_cannot_be_re_opened_through_the_edit_form(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment(5, 5);
        $loan = $this->loan($this->borrower(), $equipment, ['status' => 'Returned']);

        $this->actingAs($admin)->post('/admin/transaction/update', [
            'id' => $loan->id,
            'user_id' => $loan->user_id,
            'equipment_id' => $equipment->id,
            'borrow_date' => $loan->borrow_date->toDateString(),
            'return_date' => $loan->return_date->toDateString(),
            'quantity' => 2,
            'purpose' => 'Changed my mind',
        ])->assertSessionHasErrors('id');

        $this->assertSame('Returned', $loan->fresh()->status);
        $this->assertSame(5, $equipment->fresh()->available_quantity);
    }

    /* ---------------------------------------------------------------------
     | Rule: forms cap quantities at real availability
     --------------------------------------------------------------------- */

    public function test_a_borrower_cannot_request_more_than_is_on_the_shelf(): void
    {
        $borrower = $this->borrower();
        $equipment = $this->equipment(10, 2);

        $this->actingAs($borrower)->post('/borrower/request', [
            'equipment_id' => $equipment->id,
            'quantity' => 5,
            'remarks' => 'Thesis defence',
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('item_requests', 0);
    }

    public function test_a_borrower_cannot_request_a_retired_item(): void
    {
        $borrower = $this->borrower();
        $equipment = $this->equipment();
        $equipment->update(['retired_at' => now()]);

        $this->actingAs($borrower)->post('/borrower/request', [
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'remarks' => 'Thesis defence',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_a_decided_request_can_no_longer_be_withdrawn_by_its_owner(): void
    {
        $borrower = $this->borrower();
        $request = ItemRequest::create([
            'user_id' => $borrower->id,
            'equipment_id' => $this->equipment()->id,
            'quantity' => 1,
            'status' => 'Approved',
            'requested_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($borrower)->delete('/borrower/request/'.$request->id)->assertRedirect();
        $this->assertDatabaseHas('item_requests', ['id' => $request->id]);
    }

    /* ---------------------------------------------------------------------
     | Rule: empty dashboards lead with what needs doing
     --------------------------------------------------------------------- */

    public function test_an_admin_dashboard_with_nothing_to_do_says_so_rather_than_showing_zeroes(): void
    {
        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('Nothing outstanding');
    }

    public function test_an_admin_dashboard_leads_with_the_work_queue(): void
    {
        $admin = $this->admin();
        $equipment = $this->equipment();
        $this->loan($this->borrower(), $equipment, [
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('1 loan overdue', $html);
        // The queue comes before the counters, not after them.
        $this->assertLessThan(
            strpos($html, 'Units out on loan'),
            strpos($html, 'Needs your attention'),
            'The counters are being shown above the work queue'
        );
    }

    public function test_a_borrower_dashboard_leads_with_what_needs_doing(): void
    {
        $borrower = $this->borrower();

        $this->actingAs($borrower)->get('/borrower/dashboard')
            ->assertOk()
            ->assertSee('What needs doing')
            ->assertSee('Nothing checked out');

        $this->loan($borrower, $this->equipment(), [
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($borrower)->get('/borrower/dashboard')
            ->assertOk()
            ->assertSee('One thing is overdue')
            ->assertSee('2 days late');
    }
}
