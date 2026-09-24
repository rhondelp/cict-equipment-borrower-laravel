<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The equipment / inventory screen.
 *
 * The rule this page exists to honour: a Status column where every row reads
 * "Available" carries no information. Availability is derived from open loans
 * on every render, shown as a proportion, and is never a field anyone can type.
 *
 * The derivation itself is pinned by RedesignRulesTest; what is pinned here is
 * the screen built on top of it — the summary strip that filters, the single
 * sort control, and the quiet row actions.
 */
class EquipmentPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'Admin']);
    }

    private function item(string $name, int $quantity, int $available): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => $name.' description',
            'quantity' => $quantity,
            'available_quantity' => $available,
            'status' => $available > 0 ? 'Available' : 'Unavailable',
        ]);
    }

    /** One of each state, so every branch of availabilityState() renders. */
    private function shelf(): void
    {
        $this->item('Laptop (Dell)', 10, 10);      // all in
        $this->item('Projector (Epson)', 10, 6);   // partly out
        $this->item('Microphone Set', 10, 2);      // running low (20%)
        $this->item('Tripod', 4, 0);               // fully out
    }

    private function html(): string
    {
        return $this->actingAs($this->admin())->get('/admin/equipment')->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------ */

    /** "6 of 10 available" with a bar, not a pill that always says Available. */
    public function test_availability_is_shown_as_a_proportion_with_a_bar(): void
    {
        $this->shelf();
        $html = $this->html();

        $this->assertStringContainsString('of 10 available', $html);
        $this->assertStringContainsString('6</span> of 10 available', $html, 'The available figure is not rendered');
        // The proportion bar, width driven by the real ratio.
        $this->assertStringContainsString('style="width: 60%"', $html, 'No proportion bar for a partly-out item');
        $this->assertStringContainsString('style="width: 0%"', $html, 'No proportion bar for a fully-out item');
    }

    /** All four states appear, each carrying its own tone. */
    public function test_every_availability_state_is_rendered(): void
    {
        $this->shelf();
        $html = $this->html();

        foreach (['All in', 'Partly out', 'Running low', 'Fully out'] as $label) {
            $this->assertStringContainsString($label, $html, "The $label state is missing");
        }
    }

    /** Figures are right-aligned and tabular so a column can be read down. */
    public function test_numbers_are_right_aligned_and_tabular(): void
    {
        $this->shelf();

        $this->assertStringContainsString('text-sm text-right text-neutral-700 tabular-nums', $this->html());
    }

    /** The description frees a column by sitting under the name. */
    public function test_the_description_sits_under_the_item_name(): void
    {
        $this->shelf();
        $html = $this->html();

        $name = strpos($html, 'Projector (Epson)');
        $description = strpos($html, 'Projector (Epson) description');

        $this->assertNotFalse($description, 'The description is not rendered');
        $this->assertGreaterThan($name, $description, 'The description is not under the name');
        $this->assertStringNotContainsString('<div>Description</div>', $html, 'Description is back as its own column');
    }

    /* ------------------------------------------------------------------
     | The summary strip
     ------------------------------------------------------------------ */

    public function test_the_summary_strip_reports_the_four_figures(): void
    {
        $this->shelf();
        $html = $this->html();

        foreach (['Units on the shelf', 'Checked out', 'Running low', 'Fully out'] as $label) {
            $this->assertStringContainsString($label, $html, "The $label figure is missing");
        }
        $this->assertStringContainsString('Under 30% available', $html);
    }

    /** The two figures that name a problem hand over the rows behind them. */
    public function test_running_low_and_fully_out_filter_the_table(): void
    {
        $this->shelf();
        $html = $this->html();

        $this->assertStringContainsString('data-list-chip="low"', $html, 'Running low is not a filter');
        $this->assertStringContainsString('data-list-chip="out"', $html, 'Fully out is not a filter');
        $this->assertStringContainsString('data-list-target="#equipment-list"', $html, 'The tiles aim at no list');
        $this->assertStringContainsString('id="equipment-list"', $html, 'The list has no id for the tiles to aim at');
    }

    /** A tile with nothing behind it is not a control. */
    public function test_a_zero_figure_is_not_clickable(): void
    {
        // Everything fully in stock: nothing is low and nothing is out.
        $this->item('Laptop (Dell)', 10, 10);
        $html = $this->html();

        // Only the summary tiles carry data-list-target; the chip row below the
        // header carries data-list-chip too, so that alone cannot tell them apart.
        $this->assertStringNotContainsString(
            'data-list-target',
            $html,
            'A summary figure reading zero is still offering a filter with no rows behind it'
        );
    }

    /* ------------------------------------------------------------------
     | Chrome
     ------------------------------------------------------------------ */

    /** One sort control, not an arrow on every column. */
    public function test_there_is_one_sort_control_and_no_per_column_sorting(): void
    {
        $this->shelf();
        $html = $this->html();

        $this->assertSame(1, substr_count($html, 'data-list-sort'), 'There is not exactly one sort control');
        $this->assertStringContainsString('Least available first', $html);
        $this->assertStringContainsString('Largest stock first', $html);

        $this->assertStringNotContainsString('Show _MENU_ entries', $html);
        $this->assertStringNotContainsString('dataTables_paginate', $html);
        $this->assertStringNotContainsString('sorting_asc', $html);
    }

    /** Sorting needs keys on the rows and a container to reorder them in. */
    public function test_rows_carry_the_keys_the_sort_control_uses(): void
    {
        $this->shelf();
        $html = $this->html();

        $this->assertStringContainsString('data-list-rows', $html, 'Nothing for the sort to reorder inside');
        $this->assertStringContainsString('data-sort-name="Projector (Epson)"', $html);
        $this->assertStringContainsString('data-sort-available="6"', $html);
        $this->assertStringContainsString('data-sort-quantity="10"', $html);
    }

    /** No pagination: the list is short and paging eight rows is theatre. */
    public function test_the_list_does_not_paginate(): void
    {
        for ($i = 1; $i <= 24; $i++) {
            $this->item('Item '.$i, 5, 5);
        }

        $html = $this->html();

        $this->assertSame(24, substr_count($html, 'data-list-row '), 'Not every row is rendered');
        $this->assertStringNotContainsString('aria-label="Pagination"', $html);
    }

    /* ------------------------------------------------------------------
     | Row actions
     ------------------------------------------------------------------ */

    /**
     * Two neutral icon buttons. A filled red button on every row shouts over
     * "Add equipment", which is the action the page is actually for.
     */
    public function test_row_actions_are_quiet_with_red_on_hover_only(): void
    {
        $this->shelf();
        $html = $this->html();

        $this->assertStringContainsString('hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700', $html);

        // Scoped to the row region: the confirm dialog's own button is red on
        // purpose and lives outside the list.
        preg_match('/data-list-rows(.*?)<p data-list-empty/s', $html, $rows);
        $this->assertNotEmpty($rows, 'Could not isolate the row region');

        // Then scoped to the buttons: a danger-toned severity dot in the status
        // cell is the point of that cell, so the rule is about controls only.
        preg_match_all('/<button[^>]*>/', $rows[1], $buttons);
        $this->assertNotEmpty($buttons[0], 'No row actions rendered');

        foreach ($buttons[0] as $button) {
            $resting = preg_replace('/hover:[^\s"]+/', '', $button);
            $this->assertStringNotContainsString(
                'danger',
                $resting,
                'A row action carries destructive styling at rest rather than on hover'
            );
        }

        // Two per row — edit and remove — across the four seeded items.
        $this->assertSame(8, count($buttons[0]), 'There are not exactly two icon buttons per row');
    }

    /* ------------------------------------------------------------------
     | The guarded delete
     ------------------------------------------------------------------ */

    /** The confirm quotes the two figures that decide the answer. */
    public function test_the_remove_dialog_states_units_out_and_references(): void
    {
        $equipment = $this->item('Projector (Epson)', 10, 6);
        $borrower = User::factory()->create(['user_type' => 'Student']);
        BorrowTransaction::create([
            'user_id' => $borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(2)->toDateString(),
            'return_date' => Carbon::today()->addDays(5)->toDateString(),
            'quantity' => 4,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ]);

        $html = $this->html();

        $this->assertStringContainsString('data-fact-b="4"', $html, 'The dialog does not quote units out');
        $this->assertStringContainsString('data-fact-c="1"', $html, 'The dialog does not quote referencing records');
        $this->assertStringContainsString('delete', $html);
        // Retire is the offered default.
        $this->assertStringContainsString('Retire item', $html);
        $this->assertStringContainsString(route('admin.equipment.retire', $equipment->id), $html);
    }

    /** And the block is real, not just a disabled button. */
    public function test_hard_delete_is_refused_while_transactions_reference_the_item(): void
    {
        $equipment = $this->item('Projector (Epson)', 10, 10);
        $borrower = User::factory()->create(['user_type' => 'Student']);
        BorrowTransaction::create([
            'user_id' => $borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => Carbon::today()->subDays(9)->toDateString(),
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Lab session',
            'status' => 'Returned',
        ]);

        $this->actingAs($this->admin())
            ->delete('/admin/equipment/'.$equipment->id)
            ->assertRedirect();

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id]);
    }

    /** Retired items stay visible but stop being offered. */
    public function test_a_retired_item_is_listed_as_retired_and_is_not_lendable(): void
    {
        $equipment = $this->item('Overhead Projector', 2, 2);
        $equipment->update(['retired_at' => now(), 'status' => 'Unavailable']);

        $this->assertStringContainsString('Retired', $this->html());
        $this->assertFalse(Equipment::lendable()->where('id', $equipment->id)->exists());
    }
}
