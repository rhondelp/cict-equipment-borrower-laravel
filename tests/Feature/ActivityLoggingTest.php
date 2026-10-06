<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\ClassSchedule;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

/**
 * Every action that changes data or access writes its audit entry: the right
 * type, actor, item, person and statuses, exactly once, and nothing at all when
 * the action is refused or rolled back.
 */
class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mia;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
        $this->mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos', 'email' => 'mia.santos@nmsc.edu.ph']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function equipment(string $name = 'Projector (Epson)', int $quantity = 10, string $loanType = 'returnable'): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => 'Portable',
            'loan_type' => $loanType,
            'quantity' => $quantity,
            'available_quantity' => $quantity,
            'status' => 'Available',
        ]);
    }

    /** POST /admin/transaction for the given [equipment id => quantity]. */
    private function lend(array $lines, ?User $borrower = null)
    {
        return $this->actingAs($this->admin)->post('/admin/transaction', [
            'user_id' => ($borrower ?? $this->mia)->id,
            'equipment' => array_keys($lines),
            'quantities' => $lines,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'purpose' => 'Lab session',
        ]);
    }

    /** The one entry of a type, failing if there are none or several. */
    private function sole(string $type): ActivityLog
    {
        $entries = ActivityLog::where('type', $type)->get();
        $this->assertCount(1, $entries, "Expected exactly one {$type} entry, found {$entries->count()}.");

        return $entries->first();
    }

    /** Field-by-field, so a failure names the field. */
    private function assertEntry(ActivityLog $entry, array $expected): void
    {
        foreach ($expected as $field => $value) {
            $this->assertSame($value, $entry->{$field}, "{$entry->type}.{$field}");
        }
    }

    /* ------------------------------------------------------------------
     | Loans
     ------------------------------------------------------------------ */

    public function test_loans_group_writes_one_entry_per_event(): void
    {
        $projector = $this->equipment();
        $paper = $this->equipment('Bond paper', 50, 'non_returnable');

        $this->lend([$projector->id => 2, $paper->id => 5])->assertSessionHasNoErrors();
        $loan = BorrowTransaction::where('equipment_id', $projector->id)->sole();
        $issue = BorrowTransaction::where('equipment_id', $paper->id)->sole();

        // One row per equipment line, each with its own loan and quantity.
        $this->assertEntry($this->sole('loan_created'), [
            'actor_id' => $this->admin->id, 'actor_name' => 'Quincy Jane Oliver', 'actor_role' => 'Admin',
            'subject_user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'equipment_name' => 'Projector (Epson)',
            'borrow_transaction_id' => $loan->id, 'quantity' => 2, 'status_to' => 'Borrowed',
        ]);
        $this->assertEntry($this->sole('loan_issued'), [
            'equipment_id' => $paper->id, 'borrow_transaction_id' => $issue->id, 'quantity' => 5, 'status_to' => 'Issued',
        ]);

        // Edit: the change is in meta, old to new.
        $edit = fn (int $quantity) => $this->actingAs($this->admin)->post('/admin/transaction/update', [
            'id' => $loan->id, 'user_id' => $this->mia->id, 'equipment_id' => $projector->id,
            'borrow_date' => $loan->borrow_date->toDateString(), 'return_date' => $loan->return_date->toDateString(),
            'quantity' => $quantity, 'purpose' => 'Lab session',
        ])->assertSessionHasNoErrors();
        $edit(3);
        $updated = $this->sole('loan_updated');
        $this->assertEntry($updated, ['borrow_transaction_id' => $loan->id, 'quantity' => 3, 'subject_user_id' => $this->mia->id]);
        $this->assertSame(['old' => 2, 'new' => 3], $updated->meta['changes']['quantity']);
        $this->assertStringContainsString('quantity 2 → 3', $updated->details);

        // Saving the same values again is not an event.
        $edit(3);
        $this->sole('loan_updated');

        $this->actingAs($this->admin)->postJson('/send-email/'.$loan->id, ['type' => 'reminder'])->assertOk();
        $this->assertEntry($this->sole('loan_reminder_sent'), [
            'actor_id' => $this->admin->id, 'subject_user_id' => $this->mia->id, 'borrow_transaction_id' => $loan->id,
        ]);

        $this->actingAs($this->admin)->post('/admin/transaction/check-in', ['id' => $loan->id, 'condition' => 'Good'])
            ->assertSessionHasNoErrors();
        $checkIn = $this->sole('loan_checked_in');
        $this->assertEntry($checkIn, [
            'actor_id' => $this->admin->id, 'subject_user_id' => $this->mia->id, 'equipment_id' => $projector->id,
            'borrow_transaction_id' => $loan->id, 'quantity' => 3, 'status_from' => 'Borrowed', 'status_to' => 'Returned',
        ]);
        $this->assertSame('Good', $checkIn->meta['condition']);
        $this->assertStringContainsString('in Good condition', $checkIn->details);

        $this->actingAs($this->admin)->post('/admin/transaction/'.$issue->id.'/void', ['void_reason' => 'Recorded on the wrong item'])
            ->assertSessionHasNoErrors();
        $void = $this->sole('loan_voided');
        $this->assertEntry($void, ['borrow_transaction_id' => $issue->id, 'status_from' => 'Issued', 'status_to' => 'Void']);
        $this->assertSame('Recorded on the wrong item', $void->meta['void_reason']);

        $this->actingAs($this->admin)->delete('/admin/transaction/'.$issue->id)->assertSessionHas('success');
        $deleted = $this->sole('loan_deleted');
        $this->assertEntry($deleted, [
            'borrow_transaction_id' => null, 'equipment_id' => $paper->id, 'subject_user_id' => $this->mia->id,
            'quantity' => 5, 'status_from' => 'Void',
        ]);
        $this->assertSame($issue->id, $deleted->meta['loan_id']);
    }

    public function test_the_sweep_marks_each_loan_overdue_as_the_system_and_logs_each_reminder_sent(): void
    {
        $projector = $this->equipment();
        $leo = User::factory()->create(['user_type' => 'Student', 'name' => 'Leo Cruz']);
        $late = fn (User $who, int $days) => BorrowTransaction::create([
            'user_id' => $who->id, 'equipment_id' => $projector->id, 'quantity' => 1, 'purpose' => 'Lab',
            'borrow_date' => now()->subDays(9)->toDateString(), 'return_date' => now()->subDays($days)->toDateString(),
            'status' => 'Borrowed',
        ]);
        $first = $late($this->mia, 2);
        $second = $late($leo, 1);
        $dueToday = BorrowTransaction::create([
            'user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'quantity' => 1, 'purpose' => 'Lab',
            'borrow_date' => now()->subDays(3)->toDateString(), 'return_date' => now()->toDateString(), 'status' => 'Borrowed',
        ]);

        // Run by an admin from the screen, but the sweep is the system's doing.
        $this->actingAs($this->admin)->get('/admin/send-return-alerts')->assertOk();

        $marked = ActivityLog::where('type', 'loan_marked_overdue')->orderBy('borrow_transaction_id')->get();
        $this->assertSame([$first->id, $second->id], $marked->pluck('borrow_transaction_id')->all());
        foreach ($marked as $entry) {
            $this->assertEntry($entry, ['actor_id' => null, 'actor_name' => null, 'status_from' => 'Borrowed', 'status_to' => 'Overdue']);
        }

        $this->assertEntry($this->sole('loan_reminder_sent'), [
            'actor_id' => null, 'subject_user_id' => $this->mia->id, 'borrow_transaction_id' => $dueToday->id,
        ]);

        // A second run flips nothing new and sends nothing new, so logs nothing new.
        $this->actingAs($this->admin)->get('/admin/send-return-alerts')->assertOk();
        $this->assertSame(2, ActivityLog::where('type', 'loan_marked_overdue')->count());
        $this->sole('loan_reminder_sent');
    }

    /* ------------------------------------------------------------------
     | Requests
     ------------------------------------------------------------------ */

    public function test_requests_group_writes_one_entry_per_event(): void
    {
        $projector = $this->equipment();

        $this->actingAs($this->mia)->post('/borrower/request', ['equipment_id' => $projector->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();
        $request = ItemRequest::sole();
        $this->assertEntry($this->sole('request_submitted'), [
            'actor_id' => $this->mia->id, 'actor_role' => 'Student', 'subject_user_id' => $this->mia->id,
            'equipment_id' => $projector->id, 'quantity' => 1, 'status_to' => 'Pending',
        ]);

        $this->actingAs($this->mia)->put('/borrower/request', ['id' => $request->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $updated = $this->sole('request_updated');
        $this->assertSame(['old' => 1, 'new' => 2], $updated->meta['changes']['quantity']);

        $this->actingAs($this->mia)->delete('/borrower/request/'.$request->id)->assertSessionHas('success');
        $this->assertEntry($this->sole('request_cancelled'), [
            'actor_id' => $this->mia->id, 'equipment_id' => $projector->id, 'status_from' => 'Pending', 'status_to' => 'Cancelled',
        ]);

        // Approval: the decision, pointing at the loan it created, and the loan itself.
        $toApprove = ItemRequest::create([
            'user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'quantity' => 2,
            'status' => 'Pending', 'requested_date' => now()->toDateString(),
        ]);
        $this->actingAs($this->admin)->post('/admin/request/approve', ['id' => $toApprove->id])->assertSessionHas('success');
        $loan = BorrowTransaction::sole();
        $this->assertEntry($this->sole('request_approved'), [
            'actor_id' => $this->admin->id, 'subject_user_id' => $this->mia->id, 'equipment_id' => $projector->id,
            'borrow_transaction_id' => $loan->id, 'quantity' => 2, 'status_from' => 'Pending', 'status_to' => 'Approved',
        ]);
        $handover = $this->sole('loan_created');
        $this->assertEntry($handover, ['borrow_transaction_id' => $loan->id, 'quantity' => 2]);
        // The request id joins the loan's own details rather than replacing them.
        $this->assertSame($toApprove->id, $handover->meta['request_id']);
        $this->assertArrayHasKey('return_date', $handover->meta);

        $toDecline = ItemRequest::create([
            'user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => now()->toDateString(),
        ]);
        $this->actingAs($this->admin)->post('/admin/request/decline', ['id' => $toDecline->id, 'reason' => 'None left this week'])
            ->assertSessionHas('success');
        $this->assertEntry($this->sole('request_declined'), [
            'actor_id' => $this->admin->id, 'subject_user_id' => $this->mia->id, 'status_from' => 'Pending',
            'status_to' => 'Declined', 'details' => 'Declined: None left this week',
        ]);
    }

    /* ------------------------------------------------------------------
     | Equipment
     ------------------------------------------------------------------ */

    public function test_equipment_group_writes_one_entry_per_event(): void
    {
        $this->actingAs($this->admin)->post('/admin/equipment', ['equipment_name' => 'HDMI cable', 'quantity' => 4])
            ->assertSessionHasNoErrors();
        $cable = Equipment::where('equipment_name', 'HDMI cable')->sole();
        $this->assertEntry($this->sole('equipment_added'), [
            'actor_id' => $this->admin->id, 'equipment_id' => $cable->id, 'quantity' => 4, 'status_to' => 'Available',
        ]);

        $this->actingAs($this->admin)->post('/admin/equipment/update', [
            'id' => $cable->id, 'equipment_name' => 'HDMI cable (2 m)', 'quantity' => 6,
        ])->assertSessionHasNoErrors();
        $updated = $this->sole('equipment_updated');
        $this->assertSame(['old' => 'HDMI cable', 'new' => 'HDMI cable (2 m)'], $updated->meta['changes']['equipment_name']);
        $this->assertSame(['old' => 4, 'new' => 6], $updated->meta['changes']['quantity']);
        $this->assertSame('HDMI cable (2 m)', $updated->equipment_name);

        $this->actingAs($this->admin)->post('/admin/equipment/'.$cable->id.'/retire')->assertSessionHas('success');
        $this->assertEntry($this->sole('equipment_retired'), [
            'equipment_id' => $cable->id, 'status_from' => 'Available', 'status_to' => 'Retired',
        ]);

        $this->actingAs($this->admin)->post('/admin/equipment/'.$cable->id.'/restore')->assertSessionHas('success');
        $this->assertEntry($this->sole('equipment_restored'), [
            'equipment_id' => $cable->id, 'status_from' => 'Retired', 'status_to' => 'Available',
        ]);

        $this->actingAs($this->admin)->delete('/admin/equipment/'.$cable->id)->assertSessionHas('success');
        $deleted = $this->sole('equipment_deleted');
        $this->assertEntry($deleted, ['equipment_id' => null, 'equipment_name' => 'HDMI cable (2 m)']);
        $this->assertSame($cable->id, $deleted->meta['equipment_id']);

        // All five entries outlive the item: their keys are null, their names stay.
        $this->assertSame(5, ActivityLog::whereNull('equipment_id')->where('equipment_name', 'like', 'HDMI cable%')->count());
    }

    /* ------------------------------------------------------------------
     | Users and schedules
     ------------------------------------------------------------------ */

    public function test_users_group_writes_one_entry_per_event(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'user_type' => 'Student', 'name' => 'Carl Reyes', 'email' => 'carl.reyes@nmsc.edu.ph',
            'password' => 'pass1234', 'password_confirmation' => 'pass1234',
        ])->assertSessionHasNoErrors();
        $carl = User::where('email', 'carl.reyes@nmsc.edu.ph')->sole();
        $this->assertEntry($this->sole('user_created'), [
            'actor_id' => $this->admin->id, 'subject_user_id' => $carl->id, 'subject_name' => 'Carl Reyes', 'status_to' => 'Student',
        ]);

        // A name change and a role change are two events; the password is never logged.
        $this->actingAs($this->admin)->post('/admin/users/update', [
            'id' => $carl->id, 'user_type' => 'Instructor', 'name' => 'Carl Reyes Jr.', 'email' => $carl->email,
            'role_override_reason' => 'Teaches IT 101 this term',
            'password' => 'NewSecret99', 'password_confirmation' => 'NewSecret99',
        ])->assertSessionHasNoErrors();
        $updated = $this->sole('user_updated');
        $this->assertSame(['old' => 'Carl Reyes', 'new' => 'Carl Reyes Jr.'], $updated->meta['changes']['name']);
        $this->assertTrue($updated->meta['password_changed']);
        $this->assertEntry($this->sole('user_role_changed'), [
            'subject_user_id' => $carl->id, 'status_from' => 'Student', 'status_to' => 'Instructor',
            'details' => 'Role changed Student to Instructor: Teaches IT 101 this term',
        ]);

        $this->actingAs($this->admin)->post('/admin/users/'.$carl->id.'/suspend', ['reason' => 'Lost a cable'])->assertSessionHas('success');
        $this->assertEntry($this->sole('user_suspended'), ['subject_user_id' => $carl->id, 'status_from' => 'Active', 'status_to' => 'Suspended']);
        $this->actingAs($this->admin)->post('/admin/users/'.$carl->id.'/lift-suspension')->assertSessionHas('success');
        $this->assertEntry($this->sole('suspension_lifted'), ['subject_user_id' => $carl->id, 'status_from' => 'Suspended', 'status_to' => 'Active']);
        $this->actingAs($this->admin)->post('/admin/users/'.$carl->id.'/deactivate')->assertSessionHas('success');
        $this->assertEntry($this->sole('user_deactivated'), ['subject_user_id' => $carl->id, 'status_to' => 'Deactivated']);
        $this->actingAs($this->admin)->post('/admin/users/'.$carl->id.'/reactivate')->assertSessionHas('success');
        $this->assertEntry($this->sole('user_reactivated'), ['subject_user_id' => $carl->id, 'status_to' => 'Active']);

        $dana = User::factory()->create(['user_type' => 'Student', 'name' => 'Dana Uy', 'instructor_requested_at' => now()]);
        $ella = User::factory()->create(['user_type' => 'Student', 'name' => 'Ella Lim', 'instructor_requested_at' => now()]);
        $this->actingAs($this->admin)->post('/admin/users/'.$dana->id.'/instructor/confirm')->assertSessionHas('success');
        $this->assertEntry($this->sole('instructor_confirmed'), ['subject_user_id' => $dana->id, 'status_from' => 'Student', 'status_to' => 'Instructor']);
        $this->actingAs($this->admin)->post('/admin/users/'.$ella->id.'/instructor/decline')->assertSessionHas('success');
        $this->assertEntry($this->sole('instructor_declined'), ['subject_user_id' => $ella->id, 'status_to' => 'Student']);
        // Confirming is its own event, not also a generic role change.
        $this->sole('user_role_changed');

        // Class schedules belong to the instructor.
        $schedule = [
            'user_id' => $carl->id, 'year_level' => 'BSIT 1', 'block_name' => 'A', 'subject_code' => 'IT 101',
            'subject_name' => 'Intro to Computing', 'schedule_time' => 'MWF 8:00', 'room' => 'Lab 2',
        ];
        $this->actingAs($this->admin)->post('/admin/users/add-sched', $schedule)->assertSessionHasNoErrors();
        $row = ClassSchedule::sole();
        $this->assertEntry($this->sole('schedule_added'), ['subject_user_id' => $carl->id, 'actor_id' => $this->admin->id]);
        $this->actingAs($this->admin)->post('/admin/users/sched/update', ['id' => $row->id, 'room' => 'Lab 3'] + $schedule)
            ->assertSessionHasNoErrors();
        $this->assertSame(['old' => 'Lab 2', 'new' => 'Lab 3'], $this->sole('schedule_updated')->meta['changes']['room']);
        $this->actingAs($this->admin)->delete('/admin/users/sched/'.$row->id)->assertSessionHas('success');
        $this->assertEntry($this->sole('schedule_deleted'), ['subject_user_id' => $carl->id]);

        // Delete an account with no history: the entry names it, the key is null.
        $this->actingAs($this->admin)->delete('/admin/users/'.$ella->id)->assertSessionHas('success');
        $deleted = $this->sole('user_deleted');
        $this->assertEntry($deleted, ['subject_user_id' => null, 'subject_name' => 'Ella Lim']);
        $this->assertSame($ella->id, $deleted->meta['user_id']);

        // Public sign-up: the new person is their own actor.
        auth()->logout();
        $this->post('/register', [
            'name' => 'Nina Torres', 'email' => 'nina.torres@nmsc.edu.ph', 'requested_role' => 'Instructor',
            'password' => 'secret123', 'agree' => '1',
        ])->assertSessionHasNoErrors();
        $nina = User::where('email', 'nina.torres@nmsc.edu.ph')->sole();
        $signUp = ActivityLog::where('type', 'user_created')->where('subject_user_id', $nina->id)->sole();
        $this->assertEntry($signUp, ['actor_id' => $nina->id, 'actor_name' => 'Nina Torres', 'status_to' => 'Student']);
        $this->assertTrue($signUp->meta['instructor_requested']);

        $this->assertNoSecretsLogged(['pass1234', 'NewSecret99', 'secret123']);
    }

    /* ------------------------------------------------------------------
     | Returns
     ------------------------------------------------------------------ */

    public function test_returns_group_writes_one_entry_per_event(): void
    {
        $projector = $this->equipment();
        $this->lend([$projector->id => 1]);
        $loan = BorrowTransaction::sole();
        $this->actingAs($this->admin)->post('/admin/transaction/check-in', [
            'id' => $loan->id, 'condition' => 'Damaged', 'remarks' => 'Cracked lens',
        ])->assertSessionHasNoErrors();
        $log = ReturnLog::sole();

        $this->actingAs($this->admin)->post('/admin/logs/'.$log->id.'/resolve', ['resolution' => 'Lens replaced by the office'])
            ->assertSessionHas('success');
        $resolved = $this->sole('return_incident_resolved');
        $this->assertEntry($resolved, [
            'actor_id' => $this->admin->id, 'subject_user_id' => $this->mia->id, 'equipment_id' => $projector->id,
            'borrow_transaction_id' => $loan->id, 'status_to' => 'Resolved', 'details' => 'Resolved: Lens replaced by the office',
        ]);
        $this->assertSame($log->id, $resolved->meta['return_log_id']);

        $this->actingAs($this->admin)->post('/admin/logs/'.$log->id.'/notes', ['body' => 'The case was cracked too'])
            ->assertSessionHas('success');
        $this->assertEntry($this->sole('return_note_added'), [
            'actor_id' => $this->admin->id, 'borrow_transaction_id' => $loan->id, 'details' => 'Correction: The case was cracked too',
        ]);
    }

    /* ------------------------------------------------------------------
     | Account & system
     ------------------------------------------------------------------ */

    public function test_account_group_writes_one_entry_per_event_and_never_a_secret(): void
    {
        // A failed sign-in names the address it tried, never the password.
        $this->post('/login', ['email' => $this->mia->email, 'password' => 'WrongGuess77']);
        $this->assertEntry($this->sole('login_failed'), [
            'actor_id' => null, 'actor_name' => 'Not signed in', 'subject_user_id' => $this->mia->id,
            'details' => 'Failed sign-in for mia.santos@nmsc.edu.ph.',
        ]);

        $this->post('/login', ['email' => $this->mia->email, 'password' => 'password'])->assertRedirect();
        $this->assertEntry($this->sole('login'), ['actor_id' => $this->mia->id, 'subject_user_id' => $this->mia->id]);

        $this->post('/logout')->assertRedirect('/');
        $this->assertEntry($this->sole('logout'), ['actor_id' => $this->mia->id]);

        // A deactivated account that gets the password right is still refused.
        $gone = User::factory()->create(['user_type' => 'Student', 'email' => 'gone@nmsc.edu.ph', 'deactivated_at' => now()]);
        $this->post('/login', ['email' => 'gone@nmsc.edu.ph', 'password' => 'password']);
        $this->assertSame(2, ActivityLog::where('type', 'login_failed')->count());
        $this->sole('login');

        Notification::fake();
        $this->post('/forgot-password', ['email' => $this->mia->email])->assertSessionHasNoErrors();
        $this->assertEntry($this->sole('password_reset_requested'), [
            'actor_id' => null, 'actor_name' => 'Not signed in', 'subject_user_id' => $this->mia->id,
        ]);

        $token = Password::broker()->createToken($this->mia);
        $this->post('/reset-password', ['token' => $token, 'email' => $this->mia->email, 'password' => 'Fresh2026x'])
            ->assertRedirect(route('login'));
        $this->assertEntry($this->sole('password_reset_completed'), ['actor_id' => $this->mia->id, 'subject_user_id' => $this->mia->id]);

        $this->assertNoSecretsLogged(['WrongGuess77', 'Fresh2026x', $token]);
        $this->assertSame(0, ActivityLog::where('details', 'like', '%reset-password/%')->count());
        $this->assertNotNull($gone);
    }

    /** Nothing a password or token was typed into ever reaches the log. */
    private function assertNoSecretsLogged(array $secrets): void
    {
        foreach (ActivityLog::all() as $entry) {
            $text = json_encode($entry->getAttributes());
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, $text, "{$entry->type} contains a secret.");
            }
        }
    }

    /* ------------------------------------------------------------------
     | Refused, rolled back, and the status flip
     ------------------------------------------------------------------ */

    public function test_refused_actions_write_nothing(): void
    {
        $projector = $this->equipment('Projector (Epson)', 2);
        $loan = BorrowTransaction::create([
            'user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'quantity' => 1, 'purpose' => 'Lab',
            'borrow_date' => now()->toDateString(), 'return_date' => now()->addDays(3)->toDateString(), 'status' => 'Returned',
        ]);
        $request = ItemRequest::create([
            'user_id' => $this->mia->id, 'equipment_id' => $projector->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => now()->toDateString(),
        ]);

        $this->lend([$projector->id => 5])->assertSessionHasErrors('quantity');                                  // short stock
        $this->actingAs($this->admin)->post('/admin/transaction/'.$loan->id.'/void', [])->assertSessionHasErrors('void_reason');
        $this->actingAs($this->admin)->post('/admin/transaction/check-in', ['id' => $loan->id, 'condition' => 'Good'])
            ->assertSessionHasErrors('id');                                                                     // already returned
        $this->actingAs($this->admin)->post('/admin/request/decline', ['id' => $request->id])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post('/admin/users/'.$this->admin->id.'/suspend', ['reason' => 'Testing myself'])
            ->assertSessionHas('error');                                                                        // own account
        $this->actingAs($this->admin)->post('/admin/users/'.$this->mia->id.'/lift-suspension')->assertSessionHas('error');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_a_rolled_back_multi_item_loan_leaves_no_entry(): void
    {
        // The first line would succeed, and would even take the projector's
        // last two units; the second is short, so the whole handover rolls back.
        $projector = $this->equipment('Projector (Epson)', 2);
        $clicker = $this->equipment('Clicker', 1);

        $this->lend([$projector->id => 2, $clicker->id => 5])->assertSessionHasErrors('quantity');

        $this->assertSame(0, BorrowTransaction::count());
        $this->assertSame(0, ActivityLog::count());
        $this->assertSame(2, $projector->fresh()->available_quantity);
    }

    public function test_an_entry_written_inside_a_failed_transaction_is_rolled_back_with_it(): void
    {
        try {
            DB::transaction(function () {
                ActivityLog::record('equipment_added', ['details' => 'Never happened.']);

                throw new RuntimeException('The change failed.');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_the_status_flip_is_logged_only_when_availability_really_flips(): void
    {
        $projector = $this->equipment('Projector (Epson)', 3);

        $this->lend([$projector->id => 1]);                                     // 2 left: still Available
        $this->assertSame(0, ActivityLog::where('type', 'equipment_status_changed')->count());

        $this->lend([$projector->id => 2]);                                     // 0 left: flips
        $last = BorrowTransaction::latest('id')->first();
        $this->assertEntry($this->sole('equipment_status_changed'), [
            'equipment_id' => $projector->id, 'borrow_transaction_id' => $last->id, 'quantity' => 2,
            'status_from' => 'Available', 'status_to' => 'Unavailable', 'actor_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post('/admin/transaction/check-in', ['id' => $last->id, 'condition' => 'Good']);
        $back = ActivityLog::where('type', 'equipment_status_changed')->latest('id')->first();
        $this->assertEntry($back, ['borrow_transaction_id' => $last->id, 'status_from' => 'Unavailable', 'status_to' => 'Available']);

        $first = BorrowTransaction::oldest('id')->first();
        $this->actingAs($this->admin)->post('/admin/transaction/check-in', ['id' => $first->id, 'condition' => 'Good']);
        $this->assertSame(2, ActivityLog::where('type', 'equipment_status_changed')->count());
    }
}
