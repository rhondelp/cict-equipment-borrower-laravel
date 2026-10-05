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
 * The borrower dashboard against design-reference/Borrower Dashboard v2:
 * loans grouped by booking, the header chip, the standing band, the shelf
 * and earlier activity.
 */
class BorrowerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00'));
        $this->borrower = User::factory()->create(['user_type' => 'Student', 'name' => 'Maria Santos']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function equipment(string $name, int $quantity = 10, ?int $available = null): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name, 'description' => 'd',
            'quantity' => $quantity, 'available_quantity' => $available ?? $quantity, 'status' => 'Available',
        ]);
    }

    private function loan(Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $this->borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => '2026-09-27',
            'return_date' => '2026-09-30',
            'quantity' => 1,
            'purpose' => 'Capstone defense',
            'status' => 'Borrowed',
        ], $overrides));
    }

    private function dashboard(): string
    {
        return $this->actingAs($this->borrower)->get('/borrower/dashboard')->assertOk()->getContent();
    }

    /* ---- 1. Bookings --------------------------------------------------- */

    public function test_loans_sharing_dates_and_purpose_render_as_one_booking(): void
    {
        $first = $this->loan($this->equipment('Laptop'), ['quantity' => 2]);
        $this->loan($this->equipment('Projector'));
        $this->loan($this->equipment('HDMI Cable'), ['quantity' => 3]);

        $html = $this->dashboard();

        $this->assertStringContainsString('Return 3 items', $html);
        $this->assertSame(1, substr_count($html, 'data-booking-items'));
        $this->assertStringContainsString('Laptop ×2', $html);
        $this->assertStringContainsString('HDMI Cable ×3', $html);
        $this->assertStringContainsString('Capstone defense. Booked out Sep 27 → Sep 30.', $html);
        $this->assertSame(1, substr_count($html, 'Print slip'), 'One slip per booking, not per item');
        $this->assertStringContainsString(route('borrower.transaction.receipt', $first->id), $html);
    }

    public function test_a_different_purpose_or_date_is_a_different_booking(): void
    {
        $this->loan($this->equipment('Laptop'));
        $this->loan($this->equipment('Projector'), ['purpose' => 'Faculty meeting']);
        $this->loan($this->equipment('Speaker'), ['return_date' => '2026-10-02']);

        $html = $this->dashboard();

        $this->assertSame(3, substr_count($html, 'Print slip'));
        $this->assertStringContainsString('Return Laptop', $html);
        $this->assertStringNotContainsString('data-booking-items', $html);
    }

    public function test_a_single_item_booking_keeps_its_name_in_the_title(): void
    {
        $this->loan($this->equipment('Projector'), ['quantity' => 2]);

        $this->assertStringContainsString('Return Projector ×2', $this->dashboard());
    }

    public function test_the_rail_shows_each_date_once(): void
    {
        // Two separate bookings due the same day.
        $this->loan($this->equipment('Laptop'));
        $this->loan($this->equipment('Projector'), ['purpose' => 'Faculty meeting']);

        $html = $this->dashboard();

        $this->assertSame(2, substr_count($html, 'data-agenda-row'));
        $this->assertSame(1, preg_match_all('/>\s*30\s*</', $html), 'Sep 30 printed more than once on the rail');
    }

    public function test_the_slip_lists_every_item_in_the_booking_and_nothing_else(): void
    {
        $first = $this->loan($this->equipment('Laptop'), ['quantity' => 2]);
        $this->loan($this->equipment('Projector'));
        $this->loan($this->equipment('Speaker'), ['purpose' => 'Faculty meeting']);
        $this->loan($this->equipment('Tripod'), ['voided_at' => now()]);

        $html = $this->actingAs($this->borrower)
            ->get(route('borrower.transaction.receipt', $first->id))->assertOk()->getContent();

        $this->assertStringContainsString('Laptop × 2', $html);
        $this->assertStringContainsString('Projector × 1', $html);
        $this->assertStringNotContainsString('Speaker', $html);
        $this->assertStringNotContainsString('Tripod', $html);
    }

    public function test_the_slip_never_includes_another_borrowers_loans(): void
    {
        $first = $this->loan($this->equipment('Laptop'));
        $other = User::factory()->create(['user_type' => 'Student']);
        BorrowTransaction::create([
            'user_id' => $other->id, 'equipment_id' => $this->equipment('Projector')->id,
            'borrow_date' => '2026-09-27', 'return_date' => '2026-09-30',
            'quantity' => 1, 'purpose' => 'Capstone defense', 'status' => 'Borrowed',
        ]);

        $html = $this->actingAs($this->borrower)
            ->get(route('borrower.transaction.receipt', $first->id))->assertOk()->getContent();

        $this->assertStringNotContainsString('Projector', $html);
    }

    /* ---- 2. Header ----------------------------------------------------- */

    public function test_the_header_has_a_user_chip_and_no_request_or_logout_button(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('id="user-btn"', $html);
        $this->assertMatchesRegularExpression('/>MS<\/span>\s*Maria\s*<\/button>/', $html);
        // The eyebrow and name heading are gone; "My equipment" survives only as the tab title.
        $this->assertStringNotContainsString('>My equipment</p>', $html);
        $this->assertStringNotContainsString('>Maria Santos</h1>', $html);
        $this->assertStringNotContainsString('id="logout-btn"', $html);
        // The aside's button is the only "Request equipment" primary action.
        $this->assertStringNotContainsString('<span class="hidden sm:inline">Request</span>', $html);
        // Log out is a real POST form with a CSRF token.
        $this->assertMatchesRegularExpression(
            '/<form method="POST" action="'.preg_quote(route('logout'), '/').'">\s*<input type="hidden" name="_token"[^>]*>\s*<button type="submit"/',
            $html
        );
    }

    public function test_the_bell_dot_shows_only_when_something_is_overdue(): void
    {
        $this->loan($this->equipment('Laptop'));
        $this->assertStringNotContainsString('data-bell-alert', $this->dashboard());

        $this->loan($this->equipment('Projector'), ['borrow_date' => '2026-09-20', 'return_date' => '2026-09-25']);
        $this->assertStringContainsString('data-bell-alert', $this->dashboard());
    }

    /* ---- 3. Standing band ---------------------------------------------- */

    public function test_the_standing_band_is_navy_and_colours_only_nonzero_counts(): void
    {
        $html = $this->dashboard();
        $this->assertStringContainsString('bg-[oklch(0.32_0.09_262)]', $html);
        $this->assertStringNotContainsString('rounded-xl bg-neutral-900', $html);
        $this->assertSame(3, substr_count($html, 'tabular-nums text-white/50'), 'All three zeros should be dimmed');

        $this->loan($this->equipment('Laptop'), ['borrow_date' => '2026-09-20', 'return_date' => '2026-09-25']);
        ItemRequest::create([
            'user_id' => $this->borrower->id, 'equipment_id' => $this->equipment('Projector')->id,
            'quantity' => 1, 'status' => 'Pending', 'requested_date' => now(), 'remarks' => 'Class',
        ]);

        $html = $this->dashboard();
        $this->assertStringContainsString('text-[oklch(0.78_0.14_25)]', $html);
        $this->assertStringContainsString('text-[oklch(0.86_0.13_85)]', $html);
    }

    /* ---- 4. Shelf ------------------------------------------------------ */

    public function test_the_shelf_puts_out_then_low_first_and_shows_five(): void
    {
        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'] as $name) {
            $this->equipment($name, 10);
        }
        $this->equipment('Low item', 10, 2);
        $this->equipment('Out item', 10, 0);
        $this->equipment('Lower item', 10, 1);

        $html = $this->dashboard();

        $out = strpos($html, 'Out item');
        $lower = strpos($html, 'Lower item');
        $low = strpos($html, 'Low item');
        $alpha = strpos($html, 'Alpha');
        $this->assertTrue($out < $lower && $lower < $low && $low < $alpha, 'Expected out, then low (scarcest first), then the rest');

        $this->assertStringContainsString('7 of 8 types', $html);
        $this->assertStringContainsString('See all 8 →', $html);
        $this->assertSame(3, substr_count($html, 'hidden data-shelf-extra'));
        $this->assertSame(8, substr_count($html, 'data-shelf-track'));
    }

    public function test_shelf_bar_colour_follows_the_thirty_percent_line(): void
    {
        $this->equipment('Plenty', 10, 4);   // 40%
        $this->equipment('Edge', 10, 3);     // 30%
        $this->equipment('Gone', 10, 0);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('/Plenty.*?bg-success-500/s', $html);
        $this->assertMatchesRegularExpression('/Edge.*?bg-warning-500/s', $html);
        $this->assertMatchesRegularExpression('/data-request-equipment="\d+"[^>]*>\s*<span[^>]*>\s*<span[^>]*>Gone/s', $html);
    }

    public function test_an_empty_row_is_disabled_and_a_stocked_row_opens_the_request(): void
    {
        $stocked = $this->equipment('Stocked', 5);
        $this->equipment('Gone', 5, 0);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('/<button type="button" disabled[^>]*data-request-equipment="\d+"/', $html);
        $this->assertStringContainsString('data-request-equipment="'.$stocked->id.'"', $html);
        $this->assertSame(1, substr_count($html, 'type="button" disabled'));
    }

    /* ---- 6. Earlier activity ------------------------------------------- */

    public function test_earlier_activity_lists_returned_and_declined_with_the_reason(): void
    {
        $this->loan($this->equipment('Speaker'), [
            'borrow_date' => '2026-09-01', 'return_date' => '2026-09-05', 'status' => 'Returned',
        ]);
        $declined = ItemRequest::create([
            'user_id' => $this->borrower->id, 'equipment_id' => $this->equipment('Tripod')->id,
            'quantity' => 1, 'status' => 'Declined', 'requested_date' => '2026-09-01',
            'remarks' => 'Shoot', 'decision_reason' => 'All tripods are booked for the fair.',
        ]);
        $declined->forceFill(['decided_at' => '2026-09-02 09:00'])->save();
        $approved = ItemRequest::create([
            'user_id' => $this->borrower->id, 'equipment_id' => $this->equipment('Mic')->id,
            'quantity' => 1, 'status' => 'Approved', 'requested_date' => '2026-09-01', 'remarks' => 'x',
        ]);
        $approved->forceFill(['decided_at' => '2026-09-02 09:00'])->save();

        $html = $this->dashboard();

        $this->assertStringContainsString('▶</span>', $html);
        $this->assertStringContainsString('Show earlier activity (2)', $html);
        $this->assertStringContainsString('Declined — All tripods are booked for the fair.', $html);
        $this->assertStringNotContainsString('Approved and handed over', $html);
    }

    /* ---- Loan types ------------------------------------------------------ */

    private function typed(string $name, string $type, int $quantity = 10): Equipment
    {
        $item = $this->equipment($name, $quantity);
        $item->update(['loan_type' => $type]);

        return $item;
    }

    /** The clock is 10:00 on Sep 28: a one-hour loan out at 9:30 is due in 30 min. */
    public function test_the_dashboard_renders_all_three_loan_types(): void
    {
        $this->loan($this->typed('Projector', Equipment::LOAN_RETURNABLE));
        $this->loan($this->typed('Clicker', Equipment::LOAN_TIME_LIMITED), [
            'borrow_date' => '2026-09-28 09:30:00', 'return_date' => '2026-09-28 10:30:00', 'timed' => true,
            'purpose' => 'Thesis defense',
        ]);
        $this->loan($this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE), [
            'status' => 'Issued', 'return_date' => null, 'borrow_date' => '2026-09-27', 'quantity' => 3,
        ]);

        $html = $this->dashboard();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $agenda = collect(iterator_to_array($xpath->query('//*[@data-agenda-row]')))->map(fn ($n) => preg_replace('/\s+/', ' ', $n->textContent));

        // Returnable and timed loans are on the agenda; the timed one with its times.
        $this->assertCount(2, $agenda);
        $timed = $agenda->first(fn ($text) => str_contains($text, 'Clicker'));
        $this->assertStringContainsString('due in 30 min', $timed);
        $this->assertStringContainsString('Booked out Sep 28, 9:30 AM → 10:30 AM', $timed);

        // The issue is not something to do: no agenda row, no "due" anywhere near it.
        $this->assertFalse($agenda->contains(fn ($text) => str_contains($text, 'Patch cable')));
        $issueRow = collect(iterator_to_array($xpath->query("//*[@id='history-panel']/div")))
            ->map(fn ($n) => preg_replace('/\s+/', ' ', $n->textContent))
            ->first(fn ($text) => str_contains($text, 'Patch cable'));
        $this->assertNotNull($issueRow, 'The issue is in earlier activity');
        $this->assertStringContainsString('Patch cable ×3 Issued to you — no return needed', $issueRow);
        $this->assertStringNotContainsStringIgnoringCase('due', $issueRow);

        // Units held counts the two out, not the three issued.
        $this->assertMatchesRegularExpression('/>\s*2\s*<\/dd>\s*<dt[^>]*>units held/', $html);
    }

    public function test_the_request_form_says_what_happens_after_approval(): void
    {
        config(['office.time_limited_minutes' => 90]);
        $this->typed('Projector', Equipment::LOAN_RETURNABLE);
        $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        $this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('/data-loan-type="returnable"\s+data-loan-note="Return by a date"/', $html);
        $this->assertMatchesRegularExpression('/data-loan-type="time_limited"\s+data-loan-note="Return within 90 minutes"/', $html);
        $this->assertMatchesRegularExpression('/data-loan-type="non_returnable"\s+data-loan-note="Given to you — no return needed"/', $html);

        // Said on the row too, for the two types that differ from a normal loan.
        $this->assertSame(2, substr_count($html, 'data-request-row-note'));
        $this->assertStringContainsString('data-request-loan-note-text', $html);

        // No review time is promised anywhere on the page.
        $this->assertStringNotContainsString('within one working day', $html);
    }

    public function test_the_time_limited_note_follows_the_configured_period(): void
    {
        config(['office.time_limited_minutes' => 60]);
        $this->assertSame('Return within 1 hour', $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED)->borrowerReturnNote());

        config(['office.time_limited_minutes' => 120]);
        $this->assertSame('Return within 2 hours', Equipment::firstWhere('equipment_name', 'Clicker')->borrowerReturnNote());
    }

    public function test_the_slip_shows_times_for_a_timed_loan_and_no_return_line_for_an_issue(): void
    {
        $timed = $this->loan($this->typed('Clicker', Equipment::LOAN_TIME_LIMITED), [
            'borrow_date' => '2026-09-28 09:30:00', 'return_date' => '2026-09-28 10:30:00', 'timed' => true,
        ]);
        $issue = $this->loan($this->typed('Patch cable', Equipment::LOAN_NON_RETURNABLE), [
            'status' => 'Issued', 'return_date' => null, 'borrow_date' => '2026-09-27',
        ]);

        $slip = $this->actingAs($this->borrower)->get(route('borrower.transaction.receipt', $timed->id))->assertOk()->getContent();
        $this->assertStringContainsString('September 28, 2026 at 9:30 AM', $slip);
        $this->assertMatchesRegularExpression('/<dt>Due back<\/dt>\s*<dd>September 28, 2026 at 10:30 AM<\/dd>/', $slip);

        $slip = $this->actingAs($this->borrower)->get(route('borrower.transaction.receipt', $issue->id))->assertOk()->getContent();
        $this->assertStringContainsString('<h1>Issue Slip</h1>', $slip);
        $this->assertMatchesRegularExpression('/<dt>Issued on<\/dt>\s*<dd>September 27, 2026<\/dd>/', $slip);
        $this->assertStringNotContainsString('Return date', $slip);
        $this->assertStringNotContainsString('Due back', $slip);
        $this->assertStringContainsString('badge-issued', $slip);
        $this->assertStringContainsString('Not expected back', $slip);
    }

    public function test_the_edit_request_dialog_carries_the_items_note(): void
    {
        $clicker = $this->typed('Clicker', Equipment::LOAN_TIME_LIMITED);
        ItemRequest::create([
            'user_id' => $this->borrower->id, 'equipment_id' => $clicker->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => '2026-09-28',
        ]);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('/data-request-edit[^>]*data-loan-note="Return within 1 hour"/s', $html);
        $this->assertStringContainsString('id="edit-request-loan-note"', $html);
    }
}
