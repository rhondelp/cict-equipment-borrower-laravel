<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\ReturnLogNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The return log.
 *
 * This is an audit record — it is read when something is damaged, missing or
 * disputed, not browsed. Two properties hold it up and both are asserted
 * against the server rather than against the template: an entry is immutable,
 * and an incident stays open until someone records what was done about it.
 */
class ReturnLogsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $name = 'Quincy Jane Oliver'): User
    {
        return User::factory()->create(['user_type' => 'Admin', 'name' => $name]);
    }

    private function borrower(string $name = 'Mia Santos'): User
    {
        return User::factory()->create(['user_type' => 'Student', 'name' => $name]);
    }

    private function equipment(string $name = 'Projector (Epson)'): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => 'Portable',
            'quantity' => 10,
            'available_quantity' => 10,
            'status' => 'Available',
        ]);
    }

    private function log(array $overrides = []): ReturnLog
    {
        $equipment = $overrides['equipment'] ?? $this->equipment();
        $borrower = $overrides['borrower'] ?? $this->borrower();
        unset($overrides['equipment'], $overrides['borrower']);

        $loan = BorrowTransaction::create([
            'user_id' => $borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(10)->toDateString(),
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
            'quantity' => 1,
            'purpose' => 'Lab session',
            'status' => 'Returned',
        ]);

        return ReturnLog::create(array_merge([
            'borrow_transaction_id' => $loan->id,
            'return_date' => Carbon::today()->subDays(3),
            'condition' => 'Good',
            'user_id' => $this->admin('Receiving Staff')->id,
        ], $overrides));
    }

    private function html(): string
    {
        return $this->actingAs($this->admin())->get('/admin/logs')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | Immutability — the property, not the buttons
     ------------------------------------------------------------------ */

    /** There is no route that edits or deletes a log entry. */
    public function test_no_route_can_change_or_delete_a_return_log(): void
    {
        $writes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/logs'))
            ->reject(fn ($route) => $route->methods() === ['GET', 'HEAD'])
            ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        // Only the two append-only writes.
        sort($writes);
        $this->assertSame(
            ['POST admin/logs/{id}/notes', 'POST admin/logs/{id}/resolve'],
            $writes,
            'A route exists that can modify or remove a return log'
        );
    }

    /** And the obvious guesses 404 rather than quietly working. */
    public function test_guessed_edit_and_delete_urls_do_not_exist(): void
    {
        $log = $this->log();
        $admin = $this->admin();

        $this->actingAs($admin)->delete('/admin/logs/'.$log->id)->assertNotFound();
        $this->actingAs($admin)->put('/admin/logs/'.$log->id)->assertNotFound();
        $this->actingAs($admin)->post('/admin/logs/update')->assertNotFound();

        $this->assertDatabaseHas('return_logs', ['id' => $log->id, 'condition' => 'Good']);
    }

    /** A correction appends and leaves the original reading alone. */
    public function test_a_correction_is_appended_and_the_entry_is_untouched(): void
    {
        $log = $this->log(['condition' => 'Damaged', 'remarks' => 'Cracked lens hood.']);
        $admin = $this->admin('Quincy Jane Oliver');

        $this->actingAs($admin)
            ->post('/admin/logs/'.$log->id.'/notes', ['body' => 'The crack was present at handover.'])
            ->assertRedirect();

        $log->refresh();
        $this->assertSame('Damaged', $log->condition, 'The condition was changed');
        $this->assertSame('Cracked lens hood.', $log->remarks, 'The original note was overwritten');

        $note = ReturnLogNote::where('return_log_id', $log->id)->firstOrFail();
        $this->assertSame('The crack was present at handover.', $note->body);
        $this->assertSame($admin->id, $note->user_id, 'The note has no author');
        $this->assertNotNull($note->created_at, 'The note has no timestamp');
    }

    public function test_a_correction_needs_something_written_in_it(): void
    {
        $log = $this->log();

        $this->actingAs($this->admin())
            ->post('/admin/logs/'.$log->id.'/notes', ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('return_log_notes', 0);
    }

    /** Corrections show on the entry with who wrote them and when. */
    public function test_corrections_are_rendered_with_their_author(): void
    {
        $log = $this->log(['condition' => 'Damaged', 'remarks' => 'Cracked lens hood.']);
        ReturnLogNote::create([
            'return_log_id' => $log->id,
            'user_id' => $this->admin('Quincy Jane Oliver')->id,
            'body' => 'The crack was present at handover.',
        ]);

        $html = $this->html();

        $this->assertStringContainsString('data-log-note', $html);
        $this->assertStringContainsString('The crack was present at handover.', $html);
        $this->assertStringContainsString('Quincy Jane Oliver', $html);
        // The original is still there beside it.
        $this->assertStringContainsString('Cracked lens hood.', $html);
    }

    /* ------------------------------------------------------------------
     | Follow-up
     ------------------------------------------------------------------ */

    /** Damaged or lost with no outcome recorded leads the page. */
    public function test_incidents_without_an_outcome_lead_the_page(): void
    {
        $this->log(['condition' => 'Good']);
        $this->log(['condition' => 'Damaged', 'remarks' => 'Cracked lens hood.']);

        $html = $this->html();

        $this->assertStringContainsString('Needs follow-up', $html);
        $this->assertStringContainsString('No resolution recorded', $html);
        $this->assertLessThan(
            strpos($html, 'id="return-log-list"'),
            strpos($html, 'Needs follow-up'),
            'The follow-up section is below the archive'
        );
    }

    public function test_recording_an_outcome_closes_the_follow_up(): void
    {
        $log = $this->log(['condition' => 'Lost', 'remarks' => 'Never came back.']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => 'Charged to the student at replacement cost.'])
            ->assertRedirect();

        $log->refresh();
        $this->assertTrue($log->isResolved());
        $this->assertFalse($log->needsFollowUp());
        $this->assertSame($admin->id, $log->resolved_by);
        $this->assertNotNull($log->resolved_at);
    }

    public function test_an_outcome_must_say_something(): void
    {
        $log = $this->log(['condition' => 'Damaged']);

        $this->actingAs($this->admin())
            ->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => ''])
            ->assertSessionHasErrors('resolution');

        $this->assertNull($log->fresh()->resolved_at);
    }

    /** A clean return has nothing to resolve, and says so rather than accepting one. */
    public function test_a_good_return_cannot_be_resolved(): void
    {
        $log = $this->log(['condition' => 'Good']);

        $this->actingAs($this->admin())
            ->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => 'Nothing was wrong with it.'])
            ->assertRedirect();

        $this->assertNull($log->fresh()->resolved_at);
    }

    /** And an outcome is recorded once; after that it is a correction. */
    public function test_an_outcome_cannot_be_silently_overwritten(): void
    {
        $log = $this->log(['condition' => 'Damaged']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => 'Repaired in house.']);
        $this->actingAs($admin)->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => 'Actually written off.']);

        $this->assertSame('Repaired in house.', $log->fresh()->resolution);
    }

    /* ------------------------------------------------------------------
     | The record itself
     ------------------------------------------------------------------ */

    /** Condition leads, and the note is shown in full rather than truncated. */
    public function test_condition_is_the_first_column_and_the_note_is_not_truncated(): void
    {
        $long = 'The lens hood is cracked along the mount and the focus ring binds at the long end; '
            .'it was working when it went out on the tenth and the borrower says it was dropped in transit.';
        $this->log(['condition' => 'Damaged', 'remarks' => $long]);

        $html = $this->html();

        $this->assertStringContainsString('>Condition</div>', $html);
        $this->assertLessThan(
            strpos($html, '>Returned item</div>'),
            strpos($html, '>Condition</div>'),
            'Condition is not the first column'
        );
        $this->assertStringContainsString($long, $html, 'The condition note is truncated');
        $this->assertStringNotContainsString('truncate">'.$long, $html);
    }

    /** Both names on every row, and a missing receiver is flagged. */
    public function test_a_return_with_no_receiving_staff_is_flagged(): void
    {
        $this->log(['condition' => 'Good', 'user_id' => null]);

        $html = $this->html();

        $this->assertStringContainsString('Incomplete records', $html);
        $this->assertStringContainsString('not recorded', $html);
        $this->assertStringContainsString('data-list-chip="incomplete"', $html);
    }

    public function test_every_row_names_who_returned_and_who_received(): void
    {
        $this->log(['borrower' => $this->borrower('Mia Santos')]);

        $html = $this->html();

        $this->assertStringContainsString('Returned by', $html);
        $this->assertStringContainsString('Received by', $html);
        $this->assertStringContainsString('Mia Santos', $html);
        $this->assertStringContainsString('Receiving Staff', $html);
    }

    /* ------------------------------------------------------------------
     | Filtering and links
     ------------------------------------------------------------------ */

    public function test_the_screen_filters_by_condition_and_by_date_range(): void
    {
        $this->log(['condition' => 'Good']);
        $this->log(['condition' => 'Damaged', 'remarks' => 'Cracked.']);

        $html = $this->html();

        $this->assertStringContainsString('data-list-chip="good"', $html);
        $this->assertStringContainsString('data-list-chip="damaged"', $html);
        $this->assertStringContainsString('data-list-from', $html, 'No date range filter');
        $this->assertStringContainsString('data-list-to', $html);
        $this->assertStringContainsString('data-date="'.Carbon::today()->subDays(3)->toDateString().'"', $html);
    }

    public function test_each_entry_links_back_to_its_loan_and_forward_to_the_item(): void
    {
        $equipment = $this->equipment('Laptop (Dell)');
        $log = $this->log(['equipment' => $equipment, 'condition' => 'Good']);

        $html = $this->html();

        $this->assertStringContainsString('#loan-'.$log->borrow_transaction_id, $html);
        $this->assertStringContainsString(route('admin.logs.item', $equipment->id), $html);
    }

    /* ------------------------------------------------------------------
     | Item history
     ------------------------------------------------------------------ */

    public function test_the_item_history_shows_every_return_for_one_item(): void
    {
        $projector = $this->equipment('Projector (Epson)');
        $laptop = $this->equipment('Laptop (Dell)');

        $this->log(['equipment' => $projector, 'condition' => 'Damaged', 'remarks' => 'Cracked lens hood.']);
        $this->log(['equipment' => $projector, 'condition' => 'Good']);
        $this->log(['equipment' => $laptop, 'condition' => 'Good', 'remarks' => 'Laptop came back fine.']);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.logs.item', $projector->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Projector (Epson)', $html);
        $this->assertStringContainsString('Cracked lens hood.', $html);
        // The other item's return is not on this page.
        $this->assertStringNotContainsString('Laptop came back fine.', $html);
    }

    /** The pattern is the point: how often this item comes back damaged. */
    public function test_the_item_history_reports_the_damage_rate(): void
    {
        $projector = $this->equipment('Projector (Epson)');
        $this->log(['equipment' => $projector, 'condition' => 'Damaged']);
        $this->log(['equipment' => $projector, 'condition' => 'Damaged']);
        $this->log(['equipment' => $projector, 'condition' => 'Good']);
        $this->log(['equipment' => $projector, 'condition' => 'Good']);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.logs.item', $projector->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Returned damaged or lost', $html);
        $this->assertStringContainsString('50% of every return of this item', $html);
    }

    public function test_an_item_with_no_returns_says_so(): void
    {
        $equipment = $this->equipment('Tripod');

        $this->actingAs($this->admin())
            ->get(route('admin.logs.item', $equipment->id))
            ->assertOk()
            ->assertSee('Nothing returned yet');
    }

    /* ------------------------------------------------------------------
     | Vocabulary
     ------------------------------------------------------------------ */

    /** The four conditions the spec names, offered at check-in. */
    public function test_the_check_in_form_offers_the_four_conditions(): void
    {
        $this->assertSame(['Good', 'Minor damage', 'Damaged', 'Lost'], ReturnLog::CONDITIONS);

        $equipment = $this->equipment();
        BorrowTransaction::create([
            'user_id' => $this->borrower()->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(2)->toDateString(),
            'return_date' => Carbon::today()->addDays(2)->toDateString(),
            'quantity' => 1,
            'purpose' => 'Lab',
            'status' => 'Borrowed',
        ]);

        $html = $this->actingAs($this->admin())->get('/admin/transaction')->assertOk()->getContent();

        foreach (ReturnLog::CONDITIONS as $condition) {
            $this->assertStringContainsString('data-condition="'.$condition.'"', $html);
        }
    }

    /** Legacy values stay valid — an audit log does not get tidied retroactively. */
    public function test_the_legacy_condition_is_still_accepted(): void
    {
        $log = $this->log(['condition' => 'Missing parts']);

        $this->assertSame('warning', $log->conditionTone());
        $this->assertTrue($log->isIncident());
        $this->assertStringContainsString('Missing parts', $this->html());
    }
}
