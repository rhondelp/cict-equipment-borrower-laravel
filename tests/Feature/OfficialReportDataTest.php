<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The data the office's official report formats need, added before any
 * report UI exists ("Prompt 27"): a unit per item, course and year per
 * student, serviceability derived from unresolved return incidents, the
 * defect list, and the "Noted by" signatory in config.
 */
class OfficialReportDataTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create(['user_type' => 'Admin', 'email' => 'admin@nmsc.edu.ph']);
    }

    private function item(array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => 'Projector (Epson)',
            'quantity' => 4,
            'available_quantity' => 4,
            'status' => 'Available',
        ], $overrides));
    }

    /** A returned loan of $quantity units, logged back in $condition. */
    private function returned(Equipment $item, string $condition, array $log = [], int $quantity = 1): ReturnLog
    {
        $loan = BorrowTransaction::create([
            'user_id' => User::factory()->create(['user_type' => 'Student'])->id,
            'equipment_id' => $item->id,
            'borrow_date' => Carbon::today()->subDays(5)->toDateString(),
            'return_date' => Carbon::today()->subDay()->toDateString(),
            'quantity' => $quantity,
            'purpose' => 'Lab',
            'status' => 'Returned',
        ]);

        return ReturnLog::create(array_merge([
            'borrow_transaction_id' => $loan->id,
            'return_date' => Carbon::today()->subDay(),
            'condition' => $condition,
            'user_id' => $this->admin()->id,
        ], $log));
    }

    /* ---------------------------------------------------------------------
     | Unit
     --------------------------------------------------------------------- */

    public function test_every_item_has_a_unit_and_it_defaults_to_pc(): void
    {
        $this->assertTrue(Schema::hasColumn('equipment', 'unit'));

        $this->assertSame('pc', $this->item()->fresh()->unit, 'An item made without a unit has none');
        $this->assertSame('pc', (new Equipment)->unit, 'An unsaved model disagrees with the column default');
    }

    public function test_an_admin_adds_equipment_with_a_unit_and_the_list_shows_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/equipment', [
            'equipment_name' => 'Network tool kit', 'quantity' => 3, 'unit' => 'set',
        ])->assertSessionHasNoErrors();

        $this->assertSame('set', Equipment::where('equipment_name', 'Network tool kit')->value('unit'));

        $html = $this->actingAs($admin)->get('/admin/equipment')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/of 3 <span data-unit>set<\/span> available/', $html);
        $this->assertStringContainsString('data-unit="set"', $html, 'The edit button does not carry the unit');
        $this->assertStringContainsString('name="unit"', $html, 'The form has no unit field');
    }

    public function test_a_post_without_a_unit_means_pc_and_an_empty_or_long_one_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/equipment', ['equipment_name' => 'HDMI cable', 'quantity' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame('pc', Equipment::where('equipment_name', 'HDMI cable')->value('unit'));

        $this->actingAs($admin)->post('/admin/equipment', ['equipment_name' => 'Mouse', 'quantity' => 2, 'unit' => ''])
            ->assertSessionHasErrors('unit');
        $this->actingAs($admin)->post('/admin/equipment', ['equipment_name' => 'Mouse', 'quantity' => 2, 'unit' => str_repeat('x', 21)])
            ->assertSessionHasErrors('unit');
        $this->assertFalse(Equipment::where('equipment_name', 'Mouse')->exists());
    }

    public function test_editing_changes_the_unit_and_logs_it_and_omitting_it_keeps_it(): void
    {
        $admin = $this->admin();
        $item = $this->item(['unit' => 'set']);
        $base = ['id' => $item->id, 'equipment_name' => $item->equipment_name, 'quantity' => 4];

        // An older form that does not post the unit must not reset it to pc.
        $this->actingAs($admin)->post('/admin/equipment/update', $base)->assertSessionHasNoErrors();
        $this->assertSame('set', $item->fresh()->unit);

        $this->actingAs($admin)->post('/admin/equipment/update', $base + ['unit' => 'box'])->assertSessionHasNoErrors();
        $this->assertSame('box', $item->fresh()->unit);

        $entry = ActivityLog::where('type', 'equipment_updated')->latest('id')->firstOrFail();
        $this->assertSame(['old' => 'set', 'new' => 'box'], $entry->meta['changes']['unit'] ?? null);
    }

    public function test_the_unit_does_not_touch_stock(): void
    {
        $item = $this->item(['quantity' => 5, 'available_quantity' => 5]);

        $this->actingAs($this->admin())->post('/admin/equipment/update', [
            'id' => $item->id, 'equipment_name' => $item->equipment_name, 'quantity' => 5, 'unit' => 'pair',
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame(5, (int) $item->available_quantity);
        $this->assertSame('Available', $item->status);
    }

    /* ---------------------------------------------------------------------
     | Course and year level
     --------------------------------------------------------------------- */

    private function signup(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Angeles Bautista',
            'email' => 'maria.bautista@nmsc.edu.ph',
            'requested_role' => 'Student',
            'password' => 'lab2026pass',
            'agree' => '1',
        ], $overrides);
    }

    public function test_a_student_signs_up_with_a_course_and_year_level(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['course', 'year_level']));

        $this->post('/register', $this->signup(['course' => 'BSIT', 'year_level' => '3rd Year']))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'maria.bautista@nmsc.edu.ph')->firstOrFail();
        $this->assertSame('BSIT', $user->course);
        $this->assertSame('3rd Year', $user->year_level);
        $this->assertSame('BSIT · 3rd Year', $user->courseAndYear());
    }

    public function test_both_are_optional_and_the_year_must_come_from_the_list(): void
    {
        $this->post('/register', $this->signup())->assertSessionHasNoErrors();
        $this->assertNull(User::where('email', 'maria.bautista@nmsc.edu.ph')->value('course'));

        $this->post('/register', $this->signup(['email' => 'juan@nmsc.edu.ph', 'year_level' => 'third']))
            ->assertSessionHasErrors('year_level');
        $this->post('/register', $this->signup(['email' => 'juan@nmsc.edu.ph', 'course' => str_repeat('B', 51)]))
            ->assertSessionHasErrors('course');
        $this->assertFalse(User::where('email', 'juan@nmsc.edu.ph')->exists());
    }

    /** An instructor applicant is stored as a Student, but has no enrolment. */
    public function test_an_instructor_applicants_course_and_year_are_dropped(): void
    {
        $this->post('/register', $this->signup([
            'email' => 'rmers@nmsc.edu.ph', 'requested_role' => 'Instructor',
            'course' => 'BSIT', 'year_level' => '3rd Year',
        ]))->assertSessionHasNoErrors();

        $user = User::where('email', 'rmers@nmsc.edu.ph')->firstOrFail();
        $this->assertNull($user->course);
        $this->assertNull($user->year_level);
    }

    public function test_the_register_form_shows_the_fields_for_a_student_only(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('name="course"', $html);
        $this->assertStringContainsString('name="year_level"', $html);
        foreach (User::YEAR_LEVELS as $level) {
            $this->assertStringContainsString('<option value="'.$level.'"', $html);
        }
        // Nothing picked yet: hidden, and disabled so nothing is posted.
        $this->assertMatchesRegularExpression('/data-student-fields\s+hidden/', $html);
        $this->assertMatchesRegularExpression('/id="course"[^>]*disabled/s', $html);
    }

    public function test_an_admin_sets_course_and_year_on_a_student_and_they_are_logged(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', [
            'user_type' => 'Student', 'name' => 'Juan Dela Cruz', 'email' => 'juan@nmsc.edu.ph',
            'password' => 'pass', 'password_confirmation' => 'pass',
            'course' => 'BSCS', 'year_level' => '1st Year',
        ])->assertSessionHasNoErrors();

        $student = User::where('email', 'juan@nmsc.edu.ph')->firstOrFail();
        $this->assertSame('BSCS', $student->course);
        $this->assertSame('1st Year', $student->year_level);

        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $student->id, 'user_type' => 'Student', 'name' => $student->name, 'email' => $student->email,
            'course' => 'BSCS', 'year_level' => '2nd Year',
        ])->assertSessionHasNoErrors();
        $this->assertSame('2nd Year', $student->fresh()->year_level);

        $entry = ActivityLog::where('type', 'user_updated')->latest('id')->firstOrFail();
        $this->assertStringContainsString('year level', $entry->details);

        $html = $this->actingAs($admin)->get('/admin/users')->assertOk()->getContent();
        $this->assertStringContainsString('BSCS · 2nd Year', $html);
        $this->assertStringContainsString('data-year-level="2nd Year"', $html);
    }

    public function test_course_and_year_are_kept_for_students_only(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', [
            'user_type' => 'Instructor', 'name' => 'Ana Reyes', 'email' => 'ana@nmsc.edu.ph',
            'password' => 'pass', 'password_confirmation' => 'pass',
            'course' => 'BSIT', 'year_level' => '4th Year',
        ])->assertSessionHasNoErrors();
        $this->assertNull(User::where('email', 'ana@nmsc.edu.ph')->value('course'));

        // A student moved to Instructor loses the enrolment details.
        $student = User::factory()->create(['user_type' => 'Student', 'course' => 'BSIT', 'year_level' => '4th Year']);
        $this->actingAs($admin)->post('/admin/users/update', [
            'id' => $student->id, 'user_type' => 'Instructor', 'name' => $student->name, 'email' => $student->email,
            'course' => 'BSIT', 'year_level' => '4th Year', 'role_override_reason' => 'Hired as a lab instructor',
        ])->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Instructor', $student->user_type);
        $this->assertNull($student->course);
        $this->assertNull($student->year_level);
    }

    /* ---------------------------------------------------------------------
     | Serviceable / non-serviceable, and the defect list
     --------------------------------------------------------------------- */

    public function test_an_item_is_serviceable_until_something_comes_back_damaged(): void
    {
        $item = $this->item();
        $this->assertTrue($item->isServiceable(), 'An item with no returns is not serviceable');

        $this->returned($item, 'Good');
        $this->assertTrue($item->isServiceable(), 'A clean return made the item non-serviceable');

        $log = $this->returned($item, 'Damaged', ['remarks' => 'Lamp does not turn on']);
        $this->assertFalse($item->isServiceable());

        // Recording what was done about it makes it serviceable again.
        $log->update(['resolution' => 'Lamp replaced', 'resolved_at' => now(), 'resolved_by' => $this->admin()->id]);
        $this->assertTrue($item->isServiceable());
    }

    /**
     * Pinned on purpose: needsFollowUp() treats every condition other than
     * Good as an incident, so Lost and Minor damage count too, and the query
     * scope must agree with the model method.
     */
    public function test_serviceability_matches_needs_follow_up_for_every_condition(): void
    {
        foreach (array_merge(ReturnLog::CONDITIONS, ReturnLog::LEGACY_CONDITIONS) as $condition) {
            $item = $this->item(['equipment_name' => 'Item '.$condition]);
            $log = $this->returned($item, $condition);

            $this->assertSame(! $log->needsFollowUp(), $item->isServiceable(), $condition);
            $this->assertSame(
                $log->needsFollowUp(),
                ReturnLog::query()->needsFollowUp()->whereKey($log->id)->exists(),
                'The scope and the method disagree on '.$condition
            );
        }
    }

    public function test_the_serviceable_scopes_split_the_inventory(): void
    {
        $fine = $this->item(['equipment_name' => 'Fine']);
        $broken = $this->item(['equipment_name' => 'Broken']);
        $this->returned($fine, 'Good');
        $this->returned($broken, 'Lost');

        $this->assertSame(['Fine'], Equipment::serviceable()->pluck('equipment_name')->all());
        $this->assertSame(['Broken'], Equipment::nonServiceable()->pluck('equipment_name')->all());

        // A list can preload the answer instead of asking once per row.
        $rows = Equipment::withExists(['returnLogs as has_open_defects' => fn ($logs) => $logs->needsFollowUp()])
            ->orderBy('equipment_name')->get();
        $this->assertSame([false, true], $rows->map->isServiceable()->all());
    }

    public function test_the_defect_list_carries_the_item_quantity_and_remarks(): void
    {
        $projector = $this->item();
        $cable = $this->item(['equipment_name' => 'HDMI cable']);

        $this->returned($projector, 'Good');
        $this->returned($projector, 'Damaged', ['remarks' => 'Cracked lens'], quantity: 2);
        $this->returned($cable, 'Lost', [
            'remarks' => 'Left in room 204', 'resolution' => 'Replaced by borrower', 'resolved_at' => now(),
        ]);

        $defects = ReturnLog::defects()->get();
        $this->assertCount(1, $defects, 'Clean or resolved returns are on the defect list');

        $defect = $defects->first();
        $this->assertTrue($defect->relationLoaded('borrowTransaction'));
        $this->assertSame('Projector (Epson)', $defect->borrowTransaction->equipment->equipment_name);
        $this->assertSame(2, (int) $defect->borrowTransaction->quantity);
        $this->assertSame('Cracked lens', $defect->remarks);

        $this->assertCount(1, $projector->defects()->get());
        $this->assertCount(0, $cable->defects()->get());
    }

    /* ---------------------------------------------------------------------
     | Noted by
     --------------------------------------------------------------------- */

    public function test_the_noted_by_signatory_is_configured_and_empty_by_default(): void
    {
        $this->assertSame(['name' => '', 'position' => ''], config('office.noted_by'));

        $example = file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('OFFICE_NOTED_BY_NAME=', $example);
        $this->assertStringContainsString('OFFICE_NOTED_BY_POSITION=', $example);
    }
}
