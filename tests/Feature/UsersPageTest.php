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
 * The users screen.
 *
 * A user row with no borrowing context is unusable for the person who has to
 * decide whether to approve their next request, so standing is the content
 * here. Two states hold the rest up: role is a fact read off the email domain
 * rather than a control, and suspension is a real restriction distinct from
 * deactivation — enforced on the server, not by hiding a button.
 */
class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $name = 'Quincy Jane Oliver'): User
    {
        return User::factory()->create([
            'user_type' => 'Admin',
            'name' => $name,
            'email' => 'quincy@nmsc.edu.ph',
        ]);
    }

    private function student(string $name = 'Mia Santos', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'Student',
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@nmsc.edu.ph',
        ], $overrides));
    }

    private function equipment(): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Projector (Epson)',
            'description' => 'Portable',
            'quantity' => 30,
            'available_quantity' => 30,
            'status' => 'Available',
        ]);
    }

    private function loan(User $user, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $user->id,
            'equipment_id' => $this->equipment()->id,
            'borrow_date' => Carbon::today()->subDays(7)->toDateString(),
            'return_date' => Carbon::today()->addDays(3)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    /** Takes the acting admin when the test already made one — admin() pins a
     *  fixed email, so creating a second would collide on the unique index. */
    private function html(?User $actor = null): string
    {
        return $this->actingAs($actor ?? $this->admin())->get('/admin/users')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | Standing
     ------------------------------------------------------------------ */

    public function test_each_row_shows_units_held_overdue_and_pending_requests(): void
    {
        $student = $this->student('Mia Santos');
        $equipment = $this->equipment();

        $this->loan($student, ['quantity' => 3]);
        $this->loan($student, ['quantity' => 1, 'return_date' => Carbon::today()->subDays(4)->toDateString()]);
        ItemRequest::create([
            'user_id' => $student->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'status' => 'Pending',
            'requested_date' => Carbon::today()->toDateString(),
        ]);

        $html = $this->html();

        $this->assertStringContainsString('data-user-standing', $html);
        $this->assertStringContainsString('4 units out · 1 overdue', $html);
        $this->assertStringContainsString('1 pending request', $html);
    }

    public function test_a_borrower_holding_nothing_says_so(): void
    {
        $this->student();

        $this->assertStringContainsString('Nothing borrowed', $this->html());
    }

    /** Pending requests count only pending ones. */
    public function test_decided_requests_are_not_counted_as_pending(): void
    {
        $student = $this->student();
        $equipment = $this->equipment();

        foreach (['Approved', 'Declined'] as $status) {
            ItemRequest::create([
                'user_id' => $student->id,
                'equipment_id' => $equipment->id,
                'quantity' => 1,
                'status' => $status,
                'requested_date' => Carbon::today()->subDays(3)->toDateString(),
            ]);
        }

        $this->assertStringNotContainsString('pending request', $this->html());
    }

    /* ------------------------------------------------------------------
     | Role as a derived fact
     ------------------------------------------------------------------ */

    /** No inline role control on any row. */
    public function test_role_is_not_editable_from_the_row(): void
    {
        $this->student();
        $html = $this->html();

        $this->assertStringContainsString('data-user-role', $html);

        // The only role select on the page is inside the edit dialog.
        $this->assertSame(1, substr_count($html, 'name="user_type"'), 'More than one role control on the page');
        preg_match('/data-list-rows(.*?)<p data-list-empty/s', $html, $rows);
        $this->assertNotEmpty($rows);
        $this->assertStringNotContainsString('name="user_type"', $rows[1], 'The rows carry a role control');
    }

    /** Everyone shares the domain, so an address never contradicts a role. */
    public function test_no_row_is_flagged_against_the_email_domain(): void
    {
        $this->student('Odd One', ['user_type' => 'Instructor']);

        $this->assertStringNotContainsString('does not match', $this->html());
    }

    public function test_changing_a_role_requires_a_reason(): void
    {
        $student = $this->student('Mia Santos');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'user_type' => 'Instructor',      // differs from the stored Student
        ])->assertSessionHasErrors('role_override_reason');

        $this->assertSame('Student', $student->fresh()->user_type);
    }

    public function test_an_explained_role_change_is_recorded_with_its_author(): void
    {
        $student = $this->student('Mia Santos');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'user_type' => 'Instructor',
            'role_override_reason' => 'Teaches the lab sections while finishing her degree.',
        ])->assertRedirect();

        $student->refresh();
        $this->assertSame('Instructor', $student->user_type);
        $this->assertTrue($student->roleIsOverridden());
        $this->assertSame($admin->id, $student->role_overridden_by);
        $this->assertNotNull($student->role_overridden_at);
        $this->assertStringContainsString('Teaches the lab sections', $student->roleOverrideLine());
    }

    /**
     * Demoting is a role change like any other: with no domain to fall back
     * to, it needs its own reason, and that reason replaces the old record.
     */
    public function test_demoting_is_recorded_like_any_other_role_change(): void
    {
        $instructor = $this->student('Mia Santos', [
            'user_type' => 'Instructor',
            'role_overridden_at' => now()->subMonth(),
            'role_override_reason' => 'Was teaching.',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'email' => $instructor->email,
            'user_type' => 'Student',
        ])->assertSessionHasErrors('role_override_reason');
        $this->assertSame('Instructor', $instructor->fresh()->user_type);

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'email' => $instructor->email,
            'user_type' => 'Student',
            'role_override_reason' => 'No longer teaching this semester.',
        ])->assertSessionHasNoErrors();

        $instructor->refresh();
        $this->assertSame('Student', $instructor->user_type);
        $this->assertSame('No longer teaching this semester.', $instructor->role_override_reason);
        $this->assertSame($admin->id, $instructor->role_overridden_by);
    }

    /** Saving other fields with the role unchanged needs no reason and logs nothing. */
    public function test_an_edit_that_keeps_the_role_needs_no_reason(): void
    {
        $student = $this->student('Mia Santos');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $student->id,
            'name' => 'Mia R. Santos',
            'email' => $student->email,
            'user_type' => 'Student',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Mia R. Santos', $student->fresh()->name);
        $this->assertFalse($student->fresh()->roleIsOverridden(), 'An unchanged role was logged as a change');
    }

    /* ---------------------------------------------------------------------
     | Instructor requests from sign-up
     --------------------------------------------------------------------- */

    public function test_instructor_requests_lead_the_page_with_both_decisions(): void
    {
        $hopeful = $this->student('Rey Mercado', ['instructor_requested_at' => now()->subDay()]);

        $html = $this->html();

        $this->assertStringContainsString('data-instructor-requests', $html);
        $this->assertStringContainsString('Rey Mercado', $html);
        $this->assertStringContainsString(route('admin.users.instructor.confirm', $hopeful->id), $html);
        $this->assertStringContainsString(route('admin.users.instructor.decline', $hopeful->id), $html);
        $this->assertStringContainsString('data-list-chip="requested"', $html);
        $this->assertLessThan(
            strpos($html, 'id="user-list"'),
            strpos($html, 'data-instructor-requests'),
            'Instructor requests are not above the member list'
        );
    }

    public function test_the_section_is_absent_when_nobody_is_waiting(): void
    {
        $this->student('Mia Santos');

        $this->assertStringNotContainsString('data-instructor-requests', $this->html());
    }

    public function test_confirming_makes_an_instructor_and_records_who(): void
    {
        $hopeful = $this->student('Rey Mercado', ['instructor_requested_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.instructor.confirm', $hopeful->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $hopeful->refresh();
        $this->assertSame('Instructor', $hopeful->user_type);
        $this->assertFalse($hopeful->hasPendingInstructorRequest());
        $this->assertSame($admin->id, $hopeful->role_overridden_by);
        $this->assertStringContainsString('Confirmed instructor request', $hopeful->roleOverrideLine());
    }

    public function test_declining_keeps_a_student_and_clears_the_request(): void
    {
        $hopeful = $this->student('Rey Mercado', ['instructor_requested_at' => now()]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.instructor.decline', $hopeful->id))
            ->assertRedirect();

        $hopeful->refresh();
        $this->assertSame('Student', $hopeful->user_type);
        $this->assertFalse($hopeful->hasPendingInstructorRequest());
        $this->assertFalse($hopeful->roleIsOverridden());
    }

    /** A second click, or a stale page, must not flip a decided account. */
    public function test_a_decided_request_cannot_be_confirmed_again(): void
    {
        $student = $this->student('Mia Santos');

        $this->actingAs($this->admin())
            ->post(route('admin.users.instructor.confirm', $student->id))
            ->assertSessionHas('error');

        $this->assertSame('Student', $student->fresh()->user_type);
    }

    /** Setting a role by hand settles a pending request too. */
    public function test_a_manual_role_change_clears_a_pending_request(): void
    {
        $hopeful = $this->student('Rey Mercado', ['instructor_requested_at' => now()]);

        $this->actingAs($this->admin())->post('/admin/users/update', [
            'id' => $hopeful->id,
            'name' => $hopeful->name,
            'email' => $hopeful->email,
            'user_type' => 'Instructor',
            'role_override_reason' => 'Confirmed with the dean.',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($hopeful->fresh()->hasPendingInstructorRequest());
    }

    /* ------------------------------------------------------------------
     | Suspension
     ------------------------------------------------------------------ */

    /** Suspension is a real restriction, not a flag on a row. */
    public function test_a_suspended_borrower_cannot_request_equipment(): void
    {
        $student = $this->student();
        $equipment = $this->equipment();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users/'.$student->id.'/suspend', [
            'reason' => 'Two items from last term never came back.',
        ])->assertRedirect();

        $student->refresh();
        $this->assertTrue($student->isSuspended());
        $this->assertFalse($student->canBorrow());

        $this->actingAs($student)->post('/borrower/request', [
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'remarks' => 'Thesis defence',
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('item_requests', 0);
    }

    /** And they can still sign in — that is the difference from deactivation. */
    public function test_a_suspended_account_can_still_sign_in(): void
    {
        $student = $this->student('Mia Santos', ['suspended_at' => now(), 'suspension_reason' => 'Overdue items.']);

        $this->post('/login', ['email' => $student->email, 'password' => 'password'])
            ->assertRedirect(route('borrower.dashboard'));

        $this->assertAuthenticated();
    }

    /** A deactivated one cannot, which is the state suspension is not. */
    public function test_a_deactivated_account_still_cannot_sign_in(): void
    {
        $student = $this->student('Mia Santos', ['deactivated_at' => now()]);

        $this->post('/login', ['email' => $student->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspending_requires_a_reason(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post('/admin/users/'.$student->id.'/suspend', ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($student->fresh()->isSuspended());
    }

    /** The screen says why, and since when. */
    public function test_the_suspension_states_its_reason_and_its_date(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)->post('/admin/users/'.$student->id.'/suspend', [
            'reason' => 'Two items from last term never came back.',
        ]);

        $html = $this->html($admin);

        $this->assertStringContainsString('data-restriction-reason', $html);
        $this->assertStringContainsString('Suspended since '.Carbon::today()->format('M j'), $html);
        $this->assertStringContainsString('Two items from last term never came back.', $html);
        $this->assertStringContainsString('Quincy Jane Oliver', $html);
    }

    public function test_lifting_a_suspension_restores_borrowing(): void
    {
        $student = $this->student('Mia Santos', ['suspended_at' => now(), 'suspension_reason' => 'Overdue.']);

        $this->actingAs($this->admin())
            ->post('/admin/users/'.$student->id.'/lift-suspension')
            ->assertRedirect();

        $this->assertFalse($student->fresh()->isSuspended());
        $this->assertTrue($student->fresh()->canBorrow());
    }

    /* ------------------------------------------------------------------
     | Restricted accounts lead the page
     ------------------------------------------------------------------ */

    public function test_restricted_accounts_are_surfaced_above_the_member_list(): void
    {
        $this->student('Normal Person');
        $this->student('Locked Out', ['deactivated_at' => now()]);

        $html = $this->html();

        $this->assertStringContainsString('Restricted accounts', $html);
        $this->assertStringContainsString('data-restricted-account', $html);
        $this->assertLessThan(
            strpos($html, 'id="user-list"'),
            strpos($html, 'Restricted accounts'),
            'The restricted list is below the member list'
        );
    }

    public function test_with_nothing_restricted_the_section_is_not_rendered(): void
    {
        $this->student();

        $this->assertStringNotContainsString('data-restricted-account', $this->html());
    }

    /* ------------------------------------------------------------------
     | Deleting
     ------------------------------------------------------------------ */

    public function test_a_person_with_history_cannot_be_hard_deleted(): void
    {
        $student = $this->student();
        $this->loan($student);

        $this->actingAs($this->admin())->delete('/admin/users/'.$student->id)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_deactivating_revokes_sign_in_and_keeps_history(): void
    {
        $student = $this->student();
        $loan = $this->loan($student);

        $this->actingAs($this->admin())->post('/admin/users/'.$student->id.'/deactivate')->assertRedirect();

        $this->assertNotNull($student->fresh()->deactivated_at);
        $this->assertDatabaseHas('borrow_transactions', ['id' => $loan->id]);
    }

    /* ------------------------------------------------------------------
     | Chrome
     ------------------------------------------------------------------ */

    public function test_there_is_one_sort_control_and_no_datatables_chrome(): void
    {
        $this->student();
        $html = $this->html();

        $this->assertSame(1, substr_count($html, 'data-list-sort'), 'Not exactly one sort control');
        $this->assertStringContainsString('Most overdue first', $html);
        $this->assertStringContainsString('data-list-rows', $html);
        $this->assertStringContainsString('data-sort-name=', $html);

        $this->assertStringNotContainsString('Show _MENU_ entries', $html);
        $this->assertStringNotContainsString('dataTables_paginate', $html);
        $this->assertStringNotContainsString('code.jquery.com', $html);
    }

    public function test_the_list_does_not_paginate_under_twenty_five_rows(): void
    {
        for ($i = 1; $i <= 24; $i++) {
            $this->student('Person '.$i);
        }

        $html = $this->html();

        // 24 students plus the acting admin.
        $this->assertSame(25, substr_count($html, 'data-list-row '), 'Not every row is rendered');
        $this->assertStringNotContainsString('aria-label="Pagination"', $html);
    }

    /** Row actions stay neutral until hovered. */
    public function test_row_actions_are_quiet_at_rest(): void
    {
        $this->student();
        $html = $this->html();

        preg_match('/data-list-rows(.*?)<p data-list-empty/s', $html, $rows);
        $this->assertNotEmpty($rows);

        preg_match_all('/<button[^>]*>/', $rows[1], $buttons);
        $this->assertNotEmpty($buttons[0]);

        foreach ($buttons[0] as $button) {
            $resting = preg_replace('/hover:[^\s"]+/', '', $button);
            $this->assertStringNotContainsString('bg-danger-600', $resting, 'A row action is filled red at rest');
            $this->assertStringNotContainsString('btn-danger', $resting);
        }
    }
}
