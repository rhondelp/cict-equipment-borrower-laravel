<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Equipment categories.
 *
 * A category is free text the admin picks or types in the equipment form, so
 * the decision worth pinning is that the list cannot quietly split: "cables"
 * typed against an existing "Cables" files under "Cables". Borrowers see the
 * category as group headings in the request list and as a label on the shelf
 * panel; an install with no categories at all must look as it did before.
 */
class EquipmentCategoryTest extends TestCase
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

    private function item(string $name, ?string $category, int $quantity = 5, ?int $available = null): Equipment
    {
        $available ??= $quantity;

        return Equipment::create([
            'equipment_name' => $name,
            'category' => $category,
            'quantity' => $quantity,
            'available_quantity' => $available,
            'status' => $available > 0 ? 'Available' : 'Unavailable',
        ]);
    }

    private function dashboard(): string
    {
        return $this->actingAs($this->borrower())->get('/borrower/dashboard')->assertOk()->getContent();
    }

    /* ---- saving --------------------------------------------------------- */

    public function test_a_new_item_is_saved_with_its_category(): void
    {
        $this->actingAs($this->admin())->post('/admin/equipment', [
            'equipment_name' => 'Projector (Epson)',
            'category' => 'Projectors',
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertSame('Projectors', Equipment::firstWhere('equipment_name', 'Projector (Epson)')->category);
    }

    public function test_a_typed_category_reuses_the_existing_spelling(): void
    {
        $this->item('HDMI Cable', 'Cables');

        $this->actingAs($this->admin())->post('/admin/equipment', [
            'equipment_name' => 'VGA Cable',
            'category' => '  cables ',
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame('Cables', Equipment::firstWhere('equipment_name', 'VGA Cable')->category);
        $this->assertSame(['Cables'], Equipment::categoriesInUse()->all());
    }

    public function test_a_new_category_keeps_its_casing_and_collapses_spaces(): void
    {
        $this->assertSame('USB-C adapters', Equipment::canonicalCategory('USB-C   adapters'));
        $this->assertSame('HDMI', Equipment::canonicalCategory(' HDMI '));
    }

    public function test_a_blank_category_is_stored_as_none(): void
    {
        $this->actingAs($this->admin())->post('/admin/equipment', [
            'equipment_name' => 'Tripod',
            'category' => '   ',
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertNull(Equipment::firstWhere('equipment_name', 'Tripod')->category);
    }

    public function test_editing_can_change_and_clear_the_category(): void
    {
        $item = $this->item('Document Camera', 'Display');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/equipment/update', [
            'id' => $item->id, 'equipment_name' => 'Document Camera', 'category' => 'Cameras', 'quantity' => 5,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Cameras', $item->fresh()->category);

        $this->actingAs($admin)->post('/admin/equipment/update', [
            'id' => $item->id, 'equipment_name' => 'Document Camera', 'category' => '', 'quantity' => 5,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->category);
    }

    public function test_an_overlong_category_is_refused(): void
    {
        $this->actingAs($this->admin())->post('/admin/equipment', [
            'equipment_name' => 'Tripod',
            'category' => str_repeat('x', 61),
            'quantity' => 1,
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('equipment', ['equipment_name' => 'Tripod']);
    }

    public function test_borrowers_cannot_set_a_category(): void
    {
        $item = $this->item('Tripod', 'Cameras');

        $this->actingAs($this->borrower())->post('/admin/equipment/update', [
            'id' => $item->id, 'equipment_name' => 'Tripod', 'category' => 'Hacked', 'quantity' => 5,
        ]);

        $this->assertSame('Cameras', $item->fresh()->category);
    }

    /* ---- admin form ----------------------------------------------------- */

    public function test_the_form_suggests_categories_in_use_and_prefills_on_edit(): void
    {
        $this->item('HDMI Cable', 'Cables');
        $this->item('Projector (Epson)', 'Display');

        $html = $this->actingAs($this->admin())->get('/admin/equipment')->assertOk()->getContent();

        $this->assertStringContainsString('list="equipment-categories"', $html);
        $this->assertMatchesRegularExpression('/<datalist id="equipment-categories">\s*<option value="Cables"><\/option>\s*<option value="Display"><\/option>/', $html);
        $this->assertStringContainsString('data-category="Cables"', $html);
    }

    /* ---- borrower request list ----------------------------------------- */

    public function test_the_request_list_is_grouped_a_to_z_with_uncategorised_last(): void
    {
        $this->item('Whiteboard Markers', null);
        $this->item('Projector (Epson)', 'Display');
        $this->item('HDMI Cable', 'Cables');
        $this->item('VGA Cable', 'cables'); // stored outside the form; still its own spelling

        $html = $this->dashboard();
        $modal = substr($html, strpos($html, 'id="request-form"'));

        preg_match_all('/id="request-group-\d+"[^>]*>\s*([^<]+?)\s*</', $modal, $headings);
        $this->assertSame(['Cables', 'cables', 'Display', 'Other'], $headings[1]);

        // Each item sits under its own heading.
        $this->assertLessThan(strpos($modal, 'Projector (Epson)'), strpos($modal, '>Display'));
        $this->assertGreaterThan(strpos($modal, '>Other'), strpos($modal, 'Whiteboard Markers'));
    }

    public function test_without_any_categories_the_list_has_no_headings(): void
    {
        $this->item('Tripod', null);
        $this->item('Laptop', null);

        $html = $this->dashboard();

        $this->assertStringNotContainsString('id="request-group-', $html);
        $this->assertStringNotContainsString('role="group" aria-labelledby="request-group', $html);
        $this->assertStringContainsString('value="'.Equipment::firstWhere('equipment_name', 'Tripod')->id.'"', $html);
    }

    public function test_items_with_nothing_left_stay_disabled_inside_their_group(): void
    {
        $out = $this->item('Projector (Epson)', 'Display', 4, 0);

        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('/value="'.$out->id.'" required disabled/', $html);
    }

    /* ---- borrower shelf panel ------------------------------------------ */

    public function test_the_shelf_labels_each_item_with_its_category_after_the_count(): void
    {
        $this->item('HDMI Cable', 'Cables', 5, 3);
        $this->item('Tripod', null, 2, 2);

        $html = $this->dashboard();
        $shelf = substr($html, strpos($html, 'id="shelf-heading"'));

        $this->assertMatchesRegularExpression('/3 of 5 free<\/span>\s*<span[^>]*data-shelf-category>· Cables<\/span>/', $shelf);
        $this->assertSame(1, substr_count($shelf, 'data-shelf-category'));
    }
}
