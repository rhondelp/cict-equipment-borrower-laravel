<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Choosing an item's loan type on the equipment screen.
 *
 * The rules pinned here: a post without a type means Returnable on create and
 * "keep what it is" on edit; only the three known types are accepted; and the
 * type cannot change underneath a loan that is already out. The list shows
 * the type on every row and filters by it with the shared chip mechanism.
 */
class EquipmentLoanTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'Admin']);
    }

    private function item(string $name = 'Projector (Epson)', string $type = Equipment::LOAN_RETURNABLE, int $quantity = 10): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name, 'description' => 'd', 'loan_type' => $type,
            'quantity' => $quantity, 'available_quantity' => $quantity, 'status' => 'Available',
        ]);
    }

    private function lend(Equipment $equipment, int $quantity, string $status = 'Borrowed'): BorrowTransaction
    {
        return BorrowTransaction::create([
            'user_id' => User::factory()->create(['user_type' => 'Student'])->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'quantity' => $quantity,
            'purpose' => 'Lab session',
            'status' => $status,
        ]);
    }

    private function editPayload(Equipment $equipment, array $overrides = []): array
    {
        return array_merge([
            'id' => $equipment->id,
            'equipment_name' => $equipment->equipment_name,
            'quantity' => $equipment->quantity,
        ], $overrides);
    }

    private function html(): string
    {
        return $this->actingAs($this->admin())->get('/admin/equipment')->assertOk()->getContent();
    }

    /* ---- Saving ------------------------------------------------------------ */

    public function test_an_item_posted_without_a_type_is_returnable(): void
    {
        $this->actingAs($this->admin())->post('/admin/equipment', [
            'equipment_name' => 'HDMI cable', 'quantity' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Equipment::LOAN_RETURNABLE, Equipment::firstWhere('equipment_name', 'HDMI cable')->loan_type);
    }

    public function test_each_loan_type_saves_on_create_and_on_edit(): void
    {
        $admin = $this->admin();

        foreach (array_keys(Equipment::LOAN_TYPES) as $type) {
            $this->actingAs($admin)->post('/admin/equipment', [
                'equipment_name' => 'Item '.$type, 'loan_type' => $type, 'quantity' => 3,
            ])->assertSessionHasNoErrors();

            $this->assertSame($type, Equipment::firstWhere('equipment_name', 'Item '.$type)->loan_type);
        }

        $item = $this->item();
        foreach (array_keys(Equipment::LOAN_TYPES) as $type) {
            $this->actingAs($admin)->post('/admin/equipment/update', $this->editPayload($item, ['loan_type' => $type]))
                ->assertSessionHasNoErrors();
            $this->assertSame($type, $item->fresh()->loan_type);
        }
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/equipment', [
            'equipment_name' => 'Mystery', 'loan_type' => 'borrowed_forever', 'quantity' => 1,
        ])->assertSessionHasErrors('loan_type');
        $this->assertDatabaseMissing('equipment', ['equipment_name' => 'Mystery']);

        $item = $this->item();
        foreach (['', 'Returnable', 'time-limited'] as $bad) {
            $this->actingAs($admin)->post('/admin/equipment/update', $this->editPayload($item, ['loan_type' => $bad]))
                ->assertSessionHasErrors('loan_type');
        }
        $this->assertSame(Equipment::LOAN_RETURNABLE, $item->fresh()->loan_type);
    }

    /** An older form that does not send the field must not reset the item to Returnable. */
    public function test_an_edit_without_a_type_keeps_the_current_one(): void
    {
        $item = $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $this->actingAs($this->admin())->post('/admin/equipment/update', $this->editPayload($item, ['equipment_name' => 'Presentation clicker']))
            ->assertSessionHasNoErrors();

        $this->assertSame(Equipment::LOAN_TIME_LIMITED, $item->fresh()->loan_type);
        $this->assertSame('Presentation clicker', $item->fresh()->equipment_name);
    }

    public function test_the_type_cannot_change_while_units_are_out(): void
    {
        $item = $this->item();
        $this->lend($item, 2);

        $this->actingAs($this->admin())->from('/admin/equipment')
            ->post('/admin/equipment/update', $this->editPayload($item, [
                'equipment_name' => 'Renamed', 'loan_type' => Equipment::LOAN_TIME_LIMITED,
            ]))
            ->assertRedirect('/admin/equipment')
            ->assertSessionHasErrors('loan_type');

        $message = session('errors')->first('loan_type');
        $this->assertStringContainsString('2 units out on loan', $message);
        $this->assertStringContainsString('from Returnable to Time-Limited', $message);

        // Refused as a whole: nothing else on the form was saved either.
        $fresh = $item->fresh();
        $this->assertSame(Equipment::LOAN_RETURNABLE, $fresh->loan_type);
        $this->assertSame('Projector (Epson)', $fresh->equipment_name);
    }

    public function test_other_edits_still_work_while_units_are_out(): void
    {
        $item = $this->item();
        $this->lend($item, 2);

        $this->actingAs($this->admin())->post('/admin/equipment/update', $this->editPayload($item, [
            'loan_type' => Equipment::LOAN_RETURNABLE, 'quantity' => 12,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(10, (int) $item->fresh()->available_quantity);
    }

    public function test_the_type_can_change_once_everything_is_back(): void
    {
        $item = $this->item();
        $this->lend($item, 2, 'Returned');
        $voided = $this->lend($item, 1);
        $voided->update(['voided_at' => now(), 'void_reason' => 'Entered twice']);

        $this->actingAs($this->admin())->post('/admin/equipment/update', $this->editPayload($item, [
            'loan_type' => Equipment::LOAN_NON_RETURNABLE,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(Equipment::LOAN_NON_RETURNABLE, $item->fresh()->loan_type);
    }

    public function test_a_borrower_cannot_set_a_loan_type(): void
    {
        $item = $this->item();
        $borrower = User::factory()->create(['user_type' => 'Student']);

        $this->actingAs($borrower)->post('/admin/equipment/update', $this->editPayload($item, [
            'loan_type' => Equipment::LOAN_NON_RETURNABLE,
        ]))->assertForbidden();

        $this->assertSame(Equipment::LOAN_RETURNABLE, $item->fresh()->loan_type);
    }

    /* ---- The list ---------------------------------------------------------- */

    public function test_every_row_shows_its_loan_type_and_carries_it_as_a_chip_key(): void
    {
        $this->item('Laptop (Dell)');
        $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $html = $this->html();

        $this->assertSame(2, substr_count($html, 'data-list-row '));
        $this->assertMatchesRegularExpression('/data-loan-type="returnable"[^>]*>\s*<i[^>]*fa-rotate-left[^>]*><\/i>\s*Returnable/', $html);
        $this->assertMatchesRegularExpression('/data-loan-type="time_limited"[^>]*>\s*<i[^>]*fa-clock[^>]*><\/i>\s*Time-Limited/', $html);
        $this->assertMatchesRegularExpression('/data-chip="all-in returnable lendable"/', $html);
        $this->assertMatchesRegularExpression('/data-chip="all-in time_limited lendable"/', $html);
    }

    public function test_the_list_has_a_chip_per_loan_type_and_disables_an_unused_one(): void
    {
        $this->item('Laptop (Dell)');
        $this->item('Projector (Epson)');
        $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $html = $this->html();

        // Still one "All": the type chips join the existing group.
        $this->assertSame(1, substr_count($html, 'data-list-chip="all"'));

        $chip = function (string $type) use ($html): string {
            $this->assertSame(1, preg_match('/<button[^>]*data-list-chip="'.$type.'"[^>]*>(.*?)<\/button>/s', $html, $m), "No {$type} chip");

            return $m[0];
        };

        $this->assertStringContainsString('Returnable', $chip('returnable'));
        $this->assertMatchesRegularExpression('/tabular-nums">2</', $chip('returnable'));
        $this->assertStringNotContainsString('disabled', $this->stripClasses($chip('returnable')));

        $this->assertStringContainsString('Time-Limited', $chip('time_limited'));
        $this->assertMatchesRegularExpression('/tabular-nums">1</', $chip('time_limited'));

        $this->assertStringContainsString('Non-Returnable', $chip('non_returnable'));
        $this->assertStringContainsString('disabled', $this->stripClasses($chip('non_returnable')));
    }

    /** `disabled:` utility classes would match a naive search for the attribute. */
    private function stripClasses(string $tag): string
    {
        return preg_replace('/class="[^"]*"/', '', $tag);
    }

    public function test_the_form_offers_three_types_with_plain_hints_and_edit_prefills_the_type(): void
    {
        $this->item('Clicker', Equipment::LOAN_TIME_LIMITED);

        $html = $this->html();

        $this->assertSame(3, substr_count($html, 'name="loan_type"'));
        $this->assertMatchesRegularExpression('/value="returnable"[^>]*checked/s', $html, 'Returnable is the default choice');
        $this->assertStringContainsString('Comes back by a date', $html);
        $this->assertStringContainsString('Comes back by an exact time, such as within 1 hour', $html);
        $this->assertStringContainsString('Given out and not expected back; the issue is still recorded', $html);
        $this->assertStringContainsString('data-loan-type-locked', $html);

        $this->assertMatchesRegularExpression('/data-equipment-edit[^>]*data-loan-type="time_limited"/s', $html);
    }
}
