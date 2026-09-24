<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The admin dashboard.
 *
 * The rule: an empty dashboard is a design failure, and counters that read zero
 * are not information. So the page leads with what needs doing, every queue
 * entry links into the screen that resolves it *already filtered*, and an empty
 * queue is a sentence rather than an empty card.
 *
 * This screen aggregates the other five, so it is built last and tested against
 * the states they produce.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
    }

    private function borrower(string $name = 'Mia Santos'): User
    {
        return User::factory()->create(['user_type' => 'Student', 'name' => $name]);
    }

    private function equipment(string $name = 'Projector (Epson)', int $quantity = 10, ?int $available = null): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => 'Portable',
            'quantity' => $quantity,
            'available_quantity' => $available ?? $quantity,
            'status' => ($available ?? $quantity) > 0 ? 'Available' : 'Unavailable',
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
        return $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | The work queue leads
     ------------------------------------------------------------------ */

    public function test_the_work_queue_comes_before_the_counters(): void
    {
        $this->loan($this->borrower(), $this->equipment(), [
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $html = $this->html();

        $this->assertStringContainsString('Needs your attention', $html);
        $this->assertLessThan(
            strpos($html, 'Units out on loan'),
            strpos($html, 'Needs your attention'),
            'The counters are above the work queue'
        );
    }

    /** An empty queue is a sentence, not four cards reading zero. */
    public function test_an_empty_queue_says_so_in_words(): void
    {
        $this->equipment();

        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Nothing outstanding')
            ->assertSee('every request has been decided');
    }

    /* ------------------------------------------------------------------
     | Each queue, and where it links
     ------------------------------------------------------------------ */

    public function test_overdue_loans_link_to_the_loans_screen_already_filtered(): void
    {
        $this->loan($this->borrower(), $this->equipment(), [
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        $html = $this->html();

        $this->assertStringContainsString('1 loan overdue', $html);
        $this->assertStringContainsString(route('admin.transaction', ['filter' => 'overdue']), $html);
    }

    public function test_pending_requests_link_to_the_queue(): void
    {
        $equipment = $this->equipment();
        ItemRequest::create([
            'user_id' => $this->borrower()->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'status' => 'Pending',
            'requested_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $html = $this->html();

        $this->assertStringContainsString('1 request awaiting review', $html);
        $this->assertStringContainsString(route('admin.request'), $html);
    }

    /** New queue: a damaged or lost return with no outcome recorded. */
    public function test_unresolved_incidents_are_a_queue_entry(): void
    {
        $equipment = $this->equipment();
        $borrower = $this->borrower('Mia Santos');
        $loan = $this->loan($borrower, $equipment, ['status' => 'Returned']);

        ReturnLog::create([
            'borrow_transaction_id' => $loan->id,
            'return_date' => Carbon::today()->subDays(2),
            'condition' => 'Damaged',
            'remarks' => 'Cracked lens hood.',
            'user_id' => $this->admin()->id,
        ]);

        $html = $this->html();

        $this->assertStringContainsString('with no outcome recorded', $html);
        $this->assertStringContainsString('Record outcomes', $html);
        $this->assertStringContainsString(route('admin.logs', ['filter' => 'followup']), $html);
    }

    /** And it disappears once the outcome is recorded. */
    public function test_a_resolved_incident_leaves_the_queue(): void
    {
        $equipment = $this->equipment();
        $loan = $this->loan($this->borrower(), $equipment, ['status' => 'Returned']);
        $admin = $this->admin();

        $log = ReturnLog::create([
            'borrow_transaction_id' => $loan->id,
            'return_date' => Carbon::today()->subDays(2),
            'condition' => 'Damaged',
            'remarks' => 'Cracked lens hood.',
            'user_id' => $admin->id,
        ]);

        $this->assertStringContainsString('with no outcome recorded', $this->html());

        $log->update(['resolution' => 'Repaired in house.', 'resolved_at' => now(), 'resolved_by' => $admin->id]);

        $this->assertStringNotContainsString('with no outcome recorded', $this->html());
    }

    /** Restricted accounts, since this system has no approval queue. */
    public function test_restricted_accounts_are_a_queue_entry(): void
    {
        $this->borrower('Locked Out')->update(['deactivated_at' => now()]);
        $this->borrower('Stopped')->update(['suspended_at' => now(), 'suspension_reason' => 'Overdue items.']);

        $html = $this->html();

        $this->assertStringContainsString('2 restricted accounts', $html);
        $this->assertStringContainsString('1 suspended from borrowing', $html);
        $this->assertStringContainsString('1 unable to sign in', $html);
        $this->assertStringContainsString(route('admin.users', ['filter' => 'suspended']), $html);
    }

    /* ------------------------------------------------------------------
     | The four figures
     ------------------------------------------------------------------ */

    /** Four, and only ones that change what the admin does. */
    public function test_there_are_exactly_four_summary_figures(): void
    {
        $this->equipment();
        $html = $this->html();

        foreach (['Units out on loan', 'Overdue', 'Pending requests', 'Items fully out'] as $label) {
            $this->assertStringContainsString($label, $html, "The $label figure is missing");
        }

        // The old fourth tile was "Returned this week", which changes nothing.
        $this->assertStringNotContainsString('Returned this week', $html);
    }

    /** Overdue and out-of-stock get colour; the other two stay neutral. */
    public function test_only_the_two_problem_figures_carry_colour(): void
    {
        $equipment = $this->equipment('Tripod', 4, 0);
        $this->loan($this->borrower(), $equipment, [
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
        ]);
        ItemRequest::create([
            'user_id' => $this->borrower('Asker')->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'status' => 'Pending',
            'requested_date' => Carbon::today()->toDateString(),
        ]);

        $html = $this->html();

        // Isolate the strip: the figures are the only place this matters.
        preg_match('/Units out on loan(.*?)Needs your attention|Units out on loan(.*?)Currently out/s', $html, $strip);
        $this->assertNotEmpty($strip, 'Could not isolate the summary strip');
        $region = $strip[1] ?: ($strip[2] ?? '');

        $this->assertStringContainsString('text-danger-700', $region, 'Overdue is not coloured');
        // Pending requests has no tone even when non-zero.
        $this->assertStringNotContainsString('text-warning-700', $region, 'A neutral figure is coloured');
    }

    /* ------------------------------------------------------------------
     | Stock to watch
     ------------------------------------------------------------------ */

    public function test_equipment_that_blocks_approvals_is_listed(): void
    {
        $this->equipment('Tripod', 4, 0);           // fully out
        $this->equipment('Microphone Set', 10, 2);  // running low
        $this->equipment('Laptop (Dell)', 10, 10);  // fine

        $html = $this->html();

        $this->assertStringContainsString('Stock to watch', $html);
        $this->assertStringContainsString('Tripod', $html);
        $this->assertStringContainsString('Microphone Set', $html);
        $this->assertStringContainsString('data-stock-watch-entry', $html);
        $this->assertStringContainsString(route('admin.equipment', ['filter' => 'out']), $html);
        $this->assertStringContainsString(route('admin.equipment', ['filter' => 'low']), $html);
    }

    /** With nothing to watch it says so, rather than rendering an empty card. */
    public function test_a_healthy_shelf_says_so_in_a_sentence(): void
    {
        $this->equipment('Laptop (Dell)', 10, 10);

        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('nothing is blocking an approval right now');
    }

    /** Retired items are not "blocking" anything; they are not lendable at all. */
    public function test_retired_equipment_is_not_in_the_stock_watch(): void
    {
        $retired = $this->equipment('Overhead Projector', 2, 0);
        $retired->update(['retired_at' => now()]);

        $this->assertStringNotContainsString('Overhead Projector', $this->html());
    }

    /* ------------------------------------------------------------------
     | Activity
     ------------------------------------------------------------------ */

    /** Secondary, capped, and every entry goes somewhere. */
    public function test_activity_is_capped_and_every_entry_is_a_link(): void
    {
        $equipment = $this->equipment('Projector (Epson)', 40, 40);
        for ($i = 0; $i < 10; $i++) {
            $this->loan($this->borrower('Person '.$i), $equipment, ['quantity' => 1]);
        }

        $html = $this->html();

        $entries = substr_count($html, 'data-activity-entry');
        $this->assertGreaterThan(0, $entries, 'No activity rendered');
        $this->assertLessThanOrEqual(6, $entries, 'The activity feed is not capped');
        $this->assertStringContainsString('#loan-', $html, 'An activity entry does not link to its record');
    }

    /* ------------------------------------------------------------------
     | No decoration, and no N+1
     ------------------------------------------------------------------ */

    public function test_the_dashboard_renders_no_charts(): void
    {
        $this->equipment();
        $html = $this->html();

        $this->assertStringNotContainsString('<canvas', $html);
        $this->assertStringNotContainsString('chart.js', $html);
        $this->assertStringNotContainsString('Chart(', $html);
    }

    /**
     * Counts come from a bounded number of queries regardless of how much is on
     * the shelf. The dashboard reads five tables; it must not read them per row.
     */
    public function test_the_dashboard_does_not_n_plus_one_across_equipment(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 15; $i++) {
            $equipment = $this->equipment('Item '.$i, 10, $i % 3);
            $this->loan($this->borrower('Borrower '.$i), $equipment, ['quantity' => 1]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $count, "The dashboard fired $count queries for 15 items");
    }
}
