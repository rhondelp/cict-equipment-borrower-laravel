<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Maintenance and repair records: one activity log entry per record, and
 * nothing else — no table, no state, no stock movement.
 */
class MaintenanceLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-07 14:30:00'));
        $this->admin = User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function equipment(array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => 'Projector (Epson)',
            'description' => 'Portable',
            'quantity' => 10,
            'available_quantity' => 10,
            'status' => 'Available',
        ], $overrides));
    }

    private function log(Equipment $equipment, array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->from('/admin/equipment')
            ->post('/admin/equipment/'.$equipment->id.'/maintenance', $data);
    }

    public function test_maintenance_saves_as_maintenance_logged_dated_today_by_default(): void
    {
        $projector = $this->equipment();

        $this->log($projector, ['kind' => 'maintenance', 'summary' => 'Cleaned the filter'])
            ->assertRedirect('/admin/equipment')
            ->assertSessionHas('success');

        $entry = ActivityLog::where('type', 'maintenance_logged')->sole();
        $this->assertSame('2026-10-07 14:30:00', $entry->occurred_at->toDateTimeString());
        $this->assertSame([$projector->id, 'Projector (Epson)'], [$entry->equipment_id, $entry->equipment_name]);
        $this->assertSame([$this->admin->id, 'Quincy Jane Oliver'], [$entry->actor_id, $entry->actor_name]);
        $this->assertNull($entry->quantity);
        $this->assertSame('Cleaned the filter', $entry->details);
        $this->assertSame(['performed_on' => '2026-10-07', 'notes' => null, 'cost' => null], $entry->meta);
        $this->assertSame('Equipment', $entry->groupLabel());
    }

    public function test_repair_saves_as_repair_logged_on_the_day_it_was_done(): void
    {
        $projector = $this->equipment();

        $this->log($projector, [
            'kind' => 'repair',
            'performed_on' => '2026-10-04',
            'summary' => 'Replaced the lamp',
            'notes' => 'Lamp ordered from the supplier on Oct 1.',
            'cost' => '1250.50',
        ])->assertSessionHas('success');

        $entry = ActivityLog::where('type', 'repair_logged')->sole();
        // The day it was done, at the time it was logged.
        $this->assertSame('2026-10-04 14:30:00', $entry->occurred_at->toDateTimeString());
        $this->assertSame('Replaced the lamp', $entry->details);
        $this->assertSame('2026-10-04', $entry->meta['performed_on']);
        $this->assertSame('Lamp ordered from the supplier on Oct 1.', $entry->meta['notes']);
        $this->assertSame(1250.5, $entry->meta['cost']);
        $this->assertSame(0, ActivityLog::where('type', 'maintenance_logged')->count());
    }

    public function test_a_future_date_an_empty_summary_and_a_bad_kind_are_rejected(): void
    {
        $projector = $this->equipment();

        $this->log($projector, ['kind' => 'repair', 'performed_on' => '2026-10-08', 'summary' => 'Not yet'])
            ->assertSessionHasErrors('performed_on');
        $this->log($projector, ['kind' => 'repair', 'summary' => ''])->assertSessionHasErrors('summary');
        $this->log($projector, ['kind' => 'repair', 'summary' => str_repeat('x', 201)])->assertSessionHasErrors('summary');
        $this->log($projector, ['kind' => 'upgrade', 'summary' => 'New firmware'])->assertSessionHasErrors('kind');
        $this->log($projector, ['kind' => 'repair', 'summary' => 'Fixed', 'cost' => '-5'])->assertSessionHasErrors('cost');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_a_retired_item_is_refused(): void
    {
        $retired = $this->equipment(['retired_at' => now(), 'status' => 'Unavailable']);

        $this->log($retired, ['kind' => 'repair', 'summary' => 'Fixed the hinge'])
            ->assertRedirect('/admin/equipment')
            ->assertSessionHas('error');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_a_borrower_gets_403(): void
    {
        $projector = $this->equipment();
        $student = User::factory()->create(['user_type' => 'Student']);

        $this->log($projector, ['kind' => 'repair', 'summary' => 'Fixed it myself'], $student)->assertForbidden();

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_stock_and_availability_are_untouched(): void
    {
        // Three of ten out: servicing the item changes none of that.
        $projector = $this->equipment(['available_quantity' => 7]);
        $snapshot = fn () => $projector->fresh()->only(['quantity', 'available_quantity', 'status', 'retired_at'])
            + ['updated_at' => (string) $projector->fresh()->updated_at];
        $before = $snapshot();

        $this->log($projector, ['kind' => 'repair', 'summary' => 'Replaced the lamp'])->assertSessionHas('success');
        $this->log($projector, ['kind' => 'maintenance', 'summary' => 'Cleaned the filter'])->assertSessionHas('success');

        // Not even saved: updated_at is the same, so no column was rewritten.
        $this->assertSame($before, $snapshot());
        $this->assertSame(0, ActivityLog::where('type', 'equipment_status_changed')->count());
    }

    public function test_the_list_offers_the_action_per_row_and_the_modal_keeps_its_header_outside_the_form(): void
    {
        $projector = $this->equipment();
        $retired = $this->equipment(['equipment_name' => 'Old VGA cable', 'retired_at' => now(), 'status' => 'Unavailable']);

        $html = $this->actingAs($this->admin)->get('/admin/equipment')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Log maintenance / repair for Projector (Epson)"', $html);
        $this->assertStringContainsString('data-url="'.route('admin.equipment.maintenance', $projector->id).'"', $html);

        // The retired row's button is disabled, and says why.
        $this->assertMatchesRegularExpression(
            '/title="Retired — restore it before logging maintenance or a repair"\s+aria-label="Log maintenance \/ repair for Old VGA cable"\s+disabled/',
            $html
        );

        // div[data-modal] > header + form: the item name lives in the header,
        // so it must not be inside the form the script resets.
        $modal = str($html)->after('id="maintenance-modal"')->before('</form>');
        $this->assertStringContainsString('data-maintenance-item', (string) $modal->before('<form id="maintenance-form"'));
        $this->assertStringNotContainsString('data-maintenance-item', (string) $modal->after('<form id="maintenance-form"'));

        // Today is the latest date the picker allows.
        $this->assertStringContainsString('max="2026-10-07"', $html);
        $this->assertNotNull($retired);
    }
}
