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
 * The admin request queue.
 *
 * This is a decision queue, not a data table: the admin's question is "can I
 * say yes to this, and what happens if I do". So what is pinned here is the
 * answer to that question — the consequence of approving, computed against
 * everything else in the queue, and the conflicts that make a yes a bad one.
 *
 * The approve/decline workflow itself (reason required, stock deducted in a
 * transaction, the loan created in the same step) is pinned by RedesignRulesTest.
 */
class RequestQueuePageTest extends TestCase
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

    private function equipment(int $quantity = 10, ?int $available = null): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Projector (Epson)',
            'description' => 'Portable projector',
            'quantity' => $quantity,
            'available_quantity' => $available ?? $quantity,
            'status' => 'Available',
        ]);
    }

    private function request(User $user, Equipment $equipment, int $quantity, ?string $date = null): ItemRequest
    {
        return ItemRequest::create([
            'user_id' => $user->id,
            'equipment_id' => $equipment->id,
            'quantity' => $quantity,
            'status' => 'Pending',
            'requested_date' => $date ?? Carbon::today()->toDateString(),
            'remarks' => 'Thesis defence',
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

    private function html(): string
    {
        return $this->actingAs($this->admin())->get('/admin/request')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | What approving would do
     ------------------------------------------------------------------ */

    /** The chip answers "what happens if I say yes", not "what is on the shelf". */
    public function test_each_request_states_what_approving_would_leave(): void
    {
        $equipment = $this->equipment(10, 10);
        $this->request($this->borrower(), $equipment, 3);

        $this->assertStringContainsString('Approving leaves 7 of 10 on the shelf', $this->html());
    }

    /**
     * The figure accounts for everything earlier in the queue. Three requests
     * for four units of a ten-unit item are individually fillable and
     * collectively not, and an admin working down the list needs the running
     * total, not the same number three times.
     */
    public function test_the_consequence_accounts_for_earlier_pending_requests(): void
    {
        $equipment = $this->equipment(10, 10);
        $this->request($this->borrower('First'), $equipment, 4, Carbon::today()->subDays(3)->toDateString());
        $this->request($this->borrower('Second'), $equipment, 4, Carbon::today()->subDays(2)->toDateString());

        $html = $this->html();

        $this->assertStringContainsString('Approving leaves 6 of 10 on the shelf', $html, 'First request is wrong');
        $this->assertStringContainsString('Approving leaves 2 of 10 on the shelf', $html, 'Second ignores the first');
    }

    /** And when the queue overdraws the shelf, it says so rather than going negative. */
    public function test_an_overlapping_request_is_flagged_rather_than_showing_a_negative(): void
    {
        $equipment = $this->equipment(6, 6);
        $this->request($this->borrower('First'), $equipment, 4, Carbon::today()->subDays(3)->toDateString());
        $this->request($this->borrower('Second'), $equipment, 4, Carbon::today()->subDays(2)->toDateString());

        $html = $this->html();

        $this->assertStringContainsString('data-conflict="overlap"', $html, 'The overlap is not flagged');
        $this->assertStringContainsString('Earlier requests in this queue already claim 4 of the 6 available', $html);
        $this->assertStringNotContainsString('leaves -', $html, 'The consequence went negative');
    }

    /* ------------------------------------------------------------------
     | Conflicts
     ------------------------------------------------------------------ */

    public function test_a_request_beyond_stock_is_flagged_and_cannot_be_approved(): void
    {
        $equipment = $this->equipment(10, 2);
        $this->request($this->borrower(), $equipment, 5);

        $html = $this->html();

        $this->assertStringContainsString('data-conflict="stock"', $html);
        $this->assertStringContainsString('Asks for 5 but only 2 are on the shelf', $html);
        // And the approve button is not offered.
        $this->assertStringContainsString('Can&#039;t fill', $html);
    }

    /** An overdue borrower is a reason to think, so it is on the card. */
    public function test_a_borrower_with_an_overdue_item_is_flagged(): void
    {
        $equipment = $this->equipment(10, 10);
        $borrower = $this->borrower('Mia Santos');
        $this->loan($borrower, $equipment, ['return_date' => Carbon::today()->subDays(4)->toDateString()]);
        $this->request($borrower, $equipment, 1);

        $html = $this->html();

        $this->assertStringContainsString('data-conflict="overdue"', $html);
        $this->assertStringContainsString('Mia Santos has 1 overdue item still out', $html);
    }

    public function test_a_retired_item_is_flagged(): void
    {
        $equipment = $this->equipment(10, 10);
        $equipment->update(['retired_at' => now()]);
        $this->request($this->borrower(), $equipment, 1);

        $html = $this->html();

        $this->assertStringContainsString('data-conflict="retired"', $html);
        $this->assertStringContainsString('can no longer be lent out', $html);
    }

    /** A clean request carries no warnings at all. */
    public function test_a_clean_request_shows_no_conflicts(): void
    {
        $this->request($this->borrower(), $this->equipment(10, 10), 2);

        $this->assertStringNotContainsString('data-request-conflicts', $this->html());
    }

    /* ------------------------------------------------------------------
     | Borrower standing
     ------------------------------------------------------------------ */

    public function test_the_borrowers_standing_sits_next_to_their_name(): void
    {
        $equipment = $this->equipment(20, 20);
        $borrower = $this->borrower('Mia Santos');
        $this->loan($borrower, $equipment, ['quantity' => 3]);
        $this->request($borrower, $equipment, 1);

        $html = $this->html();

        $this->assertStringContainsString('data-borrower-standing', $html);
        $this->assertStringContainsString('Holding 3 units', $html);
    }

    public function test_a_borrower_holding_nothing_says_so(): void
    {
        $this->request($this->borrower(), $this->equipment(10, 10), 1);

        $this->assertStringContainsString('Nothing out right now', $this->html());
    }

    /** The standing counts only open, unvoided loans. */
    public function test_returned_and_voided_loans_do_not_count_towards_standing(): void
    {
        $equipment = $this->equipment(20, 20);
        $borrower = $this->borrower();
        $this->loan($borrower, $equipment, ['quantity' => 5, 'status' => 'Returned']);
        $this->loan($borrower, $equipment, ['quantity' => 7, 'voided_at' => now()]);
        $this->loan($borrower, $equipment, ['quantity' => 2]);
        $this->request($borrower, $equipment, 1);

        $this->assertStringContainsString('Holding 2 units', $this->html());
    }

    /* ------------------------------------------------------------------
     | The queue itself
     ------------------------------------------------------------------ */

    /** Pending leads; decided requests are history behind a filter. */
    public function test_the_queue_defaults_to_pending_and_history_is_separate(): void
    {
        $equipment = $this->equipment(10, 10);
        $this->request($this->borrower('Waiting'), $equipment, 1);

        ItemRequest::create([
            'user_id' => $this->borrower('Settled')->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'status' => 'Approved',
            'requested_date' => Carbon::today()->subDays(5)->toDateString(),
            'decided_at' => Carbon::today()->subDays(4),
        ]);

        $html = $this->html();

        $this->assertStringContainsString('Waiting on you', $html);
        // The queue heading comes before the archive.
        $queue = strpos($html, 'Waiting on you');
        $waiting = strpos($html, 'Waiting');
        $this->assertNotFalse($queue);
        $this->assertLessThan(strpos($html, 'Settled'), $waiting, 'A decided request is above the queue');
    }

    /** Oldest first: a queue is worked in order. */
    public function test_the_queue_is_ordered_oldest_first(): void
    {
        $equipment = $this->equipment(30, 30);
        $this->request($this->borrower('Newest'), $equipment, 1, Carbon::today()->toDateString());
        $this->request($this->borrower('Oldest'), $equipment, 1, Carbon::today()->subDays(6)->toDateString());
        $this->request($this->borrower('Middle'), $equipment, 1, Carbon::today()->subDays(3)->toDateString());

        $html = $this->html();

        $this->assertLessThan(strpos($html, 'Middle'), strpos($html, 'Oldest'), 'Oldest is not first');
        $this->assertLessThan(strpos($html, 'Newest'), strpos($html, 'Middle'), 'Newest is not last');
    }

    /** Two actions, and no control that types a status. */
    public function test_approve_and_decline_are_the_only_actions(): void
    {
        $this->request($this->borrower(), $this->equipment(10, 10), 1);
        $html = $this->html();

        $this->assertStringContainsString(route('admin.request.approve'), $html);
        $this->assertStringContainsString('data-decline-trigger', $html);
        $this->assertStringNotContainsString('name="status"', $html, 'The queue offers a status control');
    }

    /* ------------------------------------------------------------------
     | Queries
     ------------------------------------------------------------------ */

    /**
     * The standing is one grouped query for the whole queue. Twenty requests
     * must not become forty queries to draw twenty subtitles.
     */
    public function test_the_queue_does_not_n_plus_one_across_borrowers(): void
    {
        $equipment = $this->equipment(60, 60);
        for ($i = 0; $i < 12; $i++) {
            $borrower = $this->borrower('Borrower '.$i);
            $this->loan($borrower, $equipment, ['quantity' => 1]);
            $this->request($borrower, $equipment, 1);
        }

        $admin = $this->admin();
        \DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/request')->assertOk();
        $count = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Requests + users + equipment + deciders + standing + the auth/session
        // reads. Well under one-per-row either way.
        $this->assertLessThan(15, $count, "The queue fired $count queries for 12 requests");
    }
}
