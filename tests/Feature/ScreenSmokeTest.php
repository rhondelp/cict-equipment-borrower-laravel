<?php

namespace Tests\Feature;

use App\Models\BorrowTransaction;
use App\Models\ClassSchedule;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every redesigned screen against a fixture that reaches the branches
 * the other suites do not: a retired item, a voided loan, a declined request, a
 * late return with damage, a schedule with loans against it.
 *
 * These are the states that only appear once the app has been in use for a
 * while, and they are exactly the ones a template typo survives in.
 */
class ScreenSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $instructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
        $this->instructor = User::factory()->create(['user_type' => 'Instructor', 'name' => 'Richie Mers']);
        $this->student = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
        User::factory()->create(['user_type' => 'Student', 'name' => 'Retired Account', 'deactivated_at' => now()]);

        $laptop = Equipment::create([
            'equipment_name' => 'Laptop (Dell Latitude)', 'description' => '14-inch laptop',
            'quantity' => 10, 'available_quantity' => 5, 'status' => 'Available',
        ]);
        $mic = Equipment::create([
            'equipment_name' => 'Microphone Set', 'description' => 'Wireless pair',
            'quantity' => 3, 'available_quantity' => 0, 'status' => 'Unavailable',
        ]);
        Equipment::create([
            'equipment_name' => 'Overhead Projector', 'description' => 'Superseded by the Epson',
            'quantity' => 2, 'available_quantity' => 2, 'status' => 'Unavailable',
            'retired_at' => now()->subMonth(),
        ]);

        $schedule = ClassSchedule::create([
            'user_id' => $this->instructor->id, 'year_level' => '3rd Year', 'block_name' => 'A',
            'subject_code' => 'IT301', 'subject_name' => 'Systems Integration',
            'schedule_time' => 'TTh 1:00 PM – 4:00 PM', 'room' => 'Laboratory 2',
        ]);

        // Overdue, still stored as Borrowed — the nightly sweep has not run.
        BorrowTransaction::create([
            'user_id' => $this->instructor->id, 'equipment_id' => $laptop->id,
            'borrow_date' => Carbon::today()->subDays(10)->toDateString(),
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
            'quantity' => 2, 'purpose' => 'Programming lab sessions', 'status' => 'Borrowed',
            'remarks' => 'Asked for an extension by text.', 'class_schedule_id' => $schedule->id,
        ]);

        // Due today.
        BorrowTransaction::create([
            'user_id' => $this->student->id, 'equipment_id' => $laptop->id,
            'borrow_date' => Carbon::today()->subDays(5)->toDateString(),
            'return_date' => Carbon::today()->toDateString(),
            'quantity' => 3, 'purpose' => 'Org tech fair booth', 'status' => 'Borrowed',
        ]);

        // Returned late and damaged.
        $returned = BorrowTransaction::create([
            'user_id' => $this->student->id, 'equipment_id' => $mic->id,
            'borrow_date' => Carbon::today()->subDays(20)->toDateString(),
            'return_date' => Carbon::today()->subDays(14)->toDateString(),
            'quantity' => 1, 'purpose' => 'Department orientation', 'status' => 'Returned',
        ]);
        ReturnLog::create([
            'borrow_transaction_id' => $returned->id,
            'return_date' => Carbon::today()->subDays(10),
            'condition' => 'Damaged', 'remarks' => 'Bent connector — pulled from circulation.',
            'user_id' => $this->admin->id,
        ]);

        // Voided.
        BorrowTransaction::create([
            'user_id' => $this->student->id, 'equipment_id' => $mic->id,
            'borrow_date' => Carbon::today()->subDays(4)->toDateString(),
            'return_date' => Carbon::today()->addDays(3)->toDateString(),
            'quantity' => 1, 'purpose' => 'Duplicate entry', 'status' => 'Returned',
            'voided_at' => now(), 'void_reason' => 'Recorded against the wrong borrower.',
        ]);

        ItemRequest::create([
            'user_id' => $this->student->id, 'equipment_id' => $laptop->id, 'quantity' => 2,
            'status' => 'Pending', 'requested_date' => Carbon::today()->subDays(2)->toDateString(),
            'remarks' => 'Capstone presentation dry run.',
        ]);
        // Pending but unfillable: nothing left of the microphone set.
        ItemRequest::create([
            'user_id' => $this->instructor->id, 'equipment_id' => $mic->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => Carbon::today()->subDay()->toDateString(),
            'remarks' => 'Hosting the orientation.',
        ]);
        ItemRequest::create([
            'user_id' => $this->student->id, 'equipment_id' => $laptop->id, 'quantity' => 1,
            'status' => 'Declined', 'requested_date' => Carbon::today()->subDays(6)->toDateString(),
            'decision_reason' => 'A laptop already went out with your last loan.',
            'decided_at' => Carbon::today()->subDays(5), 'decided_by' => $this->admin->id,
        ]);

        Notification::create([
            'user_id' => $this->student->id,
            'message' => 'Hello Mia Santos, Laptop (Dell Latitude) ×3 is due back today.',
            'notification_type' => 'Return Notice', 'send_date' => now(),
        ]);
    }

    public static function adminScreens(): array
    {
        return [
            'dashboard' => ['/admin/dashboard'],
            'equipment' => ['/admin/equipment'],
            'users' => ['/admin/users'],
            'loans' => ['/admin/transaction'],
            'requests' => ['/admin/request'],
            'return logs' => ['/admin/logs'],
            'notifications' => ['/admin/notifications'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminScreens')]
    public function test_admin_screens_render_with_real_data(string $uri): void
    {
        $this->actingAs($this->admin)->get($uri)->assertOk();
    }

    public function test_the_loans_screen_shows_every_derived_state(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/transaction')->assertOk()->getContent();

        $this->assertStringContainsString('3 days late', $html);
        $this->assertStringContainsString('due today', $html);
        $this->assertStringContainsString('Recorded against the wrong borrower.', $html);
        $this->assertStringContainsString('Void', $html);
    }

    public function test_the_requests_screen_separates_the_queue_from_the_archive(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/request')->assertOk()->getContent();

        $this->assertStringContainsString('Waiting on you', $html);
        $this->assertStringContainsString('Decided', $html);
        // The unfillable one is marked as such rather than offering an approve
        // button that the stock check would reject.
        // Blade escapes the apostrophe, so match what actually reaches the page.
        $this->assertStringContainsString('Can&#039;t fill', $html);
        $this->assertStringContainsString('1 cannot be filled from current stock', $html);
        $this->assertStringContainsString('A laptop already went out with your last loan.', $html);
    }

    public function test_the_equipment_screen_shows_retired_items_without_offering_them(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/equipment')->assertOk()->getContent();

        $this->assertStringContainsString('Retired', $html);
        $this->assertStringContainsString('Fully out', $html);
        $this->assertStringContainsString('of 10 available', $html);
    }

    public function test_the_return_logs_screen_flags_late_and_damaged_returns(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/logs')->assertOk()->getContent();

        $this->assertStringContainsString('4 days late', $html);
        $this->assertStringContainsString('Damaged', $html);
        $this->assertStringContainsString('Bent connector', $html);
    }

    public function test_the_borrower_dashboard_renders_every_agenda_state(): void
    {
        $html = $this->actingAs($this->student)->get('/borrower/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('What needs doing', $html);
        $this->assertStringContainsString('Due soon', $html);
        $this->assertStringContainsString('Waiting', $html);
        $this->assertStringContainsString('A laptop already went out with your last loan.', $html);
        // Retired items are not offered on the shelf.
        $this->assertStringNotContainsString('Overhead Projector', $html);
    }

    public function test_the_borrower_receipt_still_renders(): void
    {
        $loan = BorrowTransaction::where('user_id', $this->student->id)->first();

        $this->actingAs($this->student)->get('/borrower/transaction/'.$loan->id.'/receipt')->assertOk();
    }
}
