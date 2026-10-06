<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\ReturnLogNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/**
 * The activity log foundation: the append-only table the reports read from,
 * the one way entries are written, the filters, and the backfill that rebuilds
 * history from the tables that came before it.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function at(string $moment): void
    {
        Carbon::setTestNow(Carbon::parse($moment));
    }

    private function admin(string $name = 'Quincy Jane Oliver'): User
    {
        return User::factory()->create(['user_type' => 'Admin', 'name' => $name]);
    }

    private function borrower(string $name = 'Mia Santos', string $type = 'Student'): User
    {
        return User::factory()->create(['user_type' => $type, 'name' => $name]);
    }

    private function equipment(string $name = 'Projector (Epson)', string $loanType = 'returnable'): Equipment
    {
        return Equipment::create([
            'equipment_name' => $name,
            'description' => 'Portable',
            'loan_type' => $loanType,
            'quantity' => 10,
            'available_quantity' => 10,
            'status' => 'Available',
        ]);
    }

    private function loan(User $borrower, Equipment $equipment, array $overrides = []): BorrowTransaction
    {
        return BorrowTransaction::create(array_merge([
            'user_id' => $borrower->id,
            'equipment_id' => $equipment->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Lab session',
            'status' => 'Borrowed',
        ], $overrides));
    }

    /* ------------------------------------------------------------------
     | record()
     ------------------------------------------------------------------ */

    public function test_record_snapshots_the_signed_in_actor_the_item_and_the_borrower(): void
    {
        $admin = $this->admin();
        $mia = $this->borrower();
        $projector = $this->equipment();
        $loan = $this->loan($mia, $projector);

        $this->actingAs($admin);
        $this->app->instance('request', Request::create('/admin/transaction', 'POST', server: ['REMOTE_ADDR' => '10.0.0.7']));
        $this->at('2026-10-07 09:15:00');

        $log = ActivityLog::record('loan_created', [
            'loan' => $loan,
            'quantity' => 2,
            'status_to' => 'Borrowed',
            'details' => 'Lent 2 × Projector (Epson) to Mia Santos.',
            'meta' => ['timed' => false],
        ]);

        $log = $log->fresh();
        $this->assertSame('2026-10-07 09:15:00', $log->occurred_at->toDateTimeString());
        $this->assertSame([$admin->id, 'Quincy Jane Oliver', 'Admin'], [$log->actor_id, $log->actor_name, $log->actor_role]);
        $this->assertSame([$mia->id, 'Mia Santos'], [$log->subject_user_id, $log->subject_name]);
        $this->assertSame([$projector->id, 'Projector (Epson)'], [$log->equipment_id, $log->equipment_name]);
        $this->assertSame($loan->id, $log->borrow_transaction_id);
        $this->assertSame('10.0.0.7', $log->ip_address);
        $this->assertSame('live', $log->source);
        $this->assertNull($log->backfill_key);
        $this->assertSame(['timed' => false], $log->meta);
        $this->assertSame('Loan recorded', $log->typeLabel());
        $this->assertSame('Loans', $log->groupLabel());
        $this->assertSame('Quincy Jane Oliver', $log->actorLabel());

        // A snapshot is a snapshot: renaming the person or the item later does
        // not rewrite what the log says happened.
        $mia->update(['name' => 'Mia Santos-Reyes']);
        $projector->update(['equipment_name' => 'Projector (Epson EB-X51)']);

        $this->assertSame('Mia Santos', $log->fresh()->subject_name);
        $this->assertSame('Projector (Epson)', $log->fresh()->equipment_name);
    }

    public function test_an_explicit_null_actor_is_the_system_even_while_someone_is_signed_in(): void
    {
        $this->actingAs($this->admin());

        $log = ActivityLog::record('loan_marked_overdue', ['actor' => null]);

        $this->assertNull($log->actor_id);
        $this->assertNull($log->actor_name);
        $this->assertNull($log->actor_role);
        $this->assertSame('System', $log->actorLabel());
    }

    public function test_explicit_models_and_columns_win_over_the_ones_taken_from_the_loan(): void
    {
        $mia = $this->borrower();
        $leo = $this->borrower('Leo Cruz');
        $loan = $this->loan($mia, $this->equipment());

        $log = ActivityLog::record('loan_reminder_sent', [
            'actor' => null,
            'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
            'loan' => $loan,
            'subject' => $leo,
            'occurred_at' => Carbon::parse('2026-09-01 08:00:00'),
        ]);

        $this->assertSame('Leo Cruz', $log->subject_name);
        $this->assertSame('Projector (Epson)', $log->equipment_name);
        $this->assertSame('Not recorded', $log->actorLabel());
        $this->assertSame('2026-09-01 08:00:00', $log->fresh()->occurred_at->toDateTimeString());
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActivityLog::record('loan_teleported');
    }

    /* ------------------------------------------------------------------
     | Append-only
     ------------------------------------------------------------------ */

    public function test_an_entry_cannot_be_changed_or_deleted_by_any_eloquent_path(): void
    {
        $this->at('2026-10-07 09:00:00');
        $log = ActivityLog::record('login', ['actor' => $this->admin(), 'details' => 'Signed in.']);
        $id = $log->id;
        // A minute later, so touch() has a timestamp to change.
        $this->at('2026-10-07 09:01:00');

        $attempts = [
            'update()' => fn () => $log->update(['details' => 'Edited.']),
            'save()' => function () use ($id) {
                $row = ActivityLog::find($id);
                $row->details = 'Edited.';
                $row->save();
            },
            'touch()' => fn () => ActivityLog::find($id)->touch(),
            'delete()' => fn () => ActivityLog::find($id)->delete(),
            'destroy()' => fn () => ActivityLog::destroy($id),
            'query update' => fn () => ActivityLog::whereKey($id)->update(['details' => 'Edited.']),
            'query delete' => fn () => ActivityLog::whereKey($id)->delete(),
            'query increment' => fn () => ActivityLog::whereKey($id)->increment('quantity'),
        ];

        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("{$label} changed an activity log entry.");
            } catch (LogicException $e) {
                $this->assertStringContainsString('append-only', $e->getMessage(), $label);
            }
        }

        $this->assertSame(1, ActivityLog::count());
        $this->assertSame('Signed in.', ActivityLog::find($id)->details);
    }

    public function test_deleting_the_person_or_the_item_keeps_the_entry_and_its_names(): void
    {
        $admin = $this->admin();
        $mia = $this->borrower();
        $cable = $this->equipment('HDMI cable');

        $log = ActivityLog::record('equipment_added', ['actor' => $admin, 'subject' => $mia, 'equipment' => $cable]);

        $cable->delete();
        $mia->delete();
        $admin->delete();

        $log = ActivityLog::find($log->id);
        $this->assertNotNull($log);
        $this->assertNull($log->actor_id);
        $this->assertNull($log->subject_user_id);
        $this->assertNull($log->equipment_id);
        $this->assertSame('Quincy Jane Oliver', $log->actor_name);
        $this->assertSame('Mia Santos', $log->subject_name);
        $this->assertSame('HDMI cable', $log->equipment_name);
    }

    /* ------------------------------------------------------------------
     | Types
     ------------------------------------------------------------------ */

    public function test_every_type_sits_in_a_known_group_and_the_groups_run_in_report_order(): void
    {
        $groupsInTypeOrder = array_values(array_unique(array_column(ActivityLog::TYPES, 'group')));

        $this->assertSame(['Loans', 'Requests', 'Equipment', 'Users', 'Returns', 'Account & system'], ActivityLog::GROUPS);
        $this->assertSame(ActivityLog::GROUPS, $groupsInTypeOrder);

        foreach (ActivityLog::TYPES as $key => $type) {
            $this->assertMatchesRegularExpression('/^[a-z_]{1,40}$/', $key);
            $this->assertNotSame('', $type['label']);
        }

        // The loan-type and schedule edit/delete features exist, so their events do.
        foreach (['loan_issued', 'schedule_updated', 'schedule_deleted'] as $key) {
            $this->assertArrayHasKey($key, ActivityLog::TYPES);
        }

        $this->assertSame(['return_incident_resolved', 'return_note_added'], ActivityLog::typesInGroup('returns'));
    }

    /* ------------------------------------------------------------------
     | filter() and newestFirst()
     ------------------------------------------------------------------ */

    private function entryAt(string $moment, string $type = 'login', array $attrs = []): ActivityLog
    {
        return ActivityLog::record($type, ['actor' => null, 'occurred_at' => Carbon::parse($moment)] + $attrs);
    }

    private function ids($query): array
    {
        return $query->orderBy('id')->pluck('id')->all();
    }

    public function test_the_date_filter_includes_the_whole_end_day_and_nothing_either_side(): void
    {
        $before = $this->entryAt('2026-10-04 23:59:59');
        $start = $this->entryAt('2026-10-05 00:00:00');
        $endLate = $this->entryAt('2026-10-06 23:30:00');
        $after = $this->entryAt('2026-10-07 00:00:00');

        $this->assertSame(
            [$start->id, $endLate->id],
            $this->ids(ActivityLog::filter(['from' => '2026-10-05', 'to' => '2026-10-06']))
        );
        $this->assertSame(
            [$start->id, $endLate->id, $after->id],
            $this->ids(ActivityLog::filter(['from' => '2026-10-05']))
        );
        $this->assertSame(
            [$before->id],
            $this->ids(ActivityLog::filter(['to' => '2026-10-04']))
        );

        // Blank and unreadable values are ignored, not fatal.
        $this->assertCount(4, ActivityLog::filter(['from' => '', 'to' => 'not a date', 'q' => '  '])->get());
    }

    public function test_the_type_filter_takes_a_type_key_or_a_group_name(): void
    {
        $created = $this->entryAt('2026-10-05 09:00', 'loan_created');
        $checkedIn = $this->entryAt('2026-10-05 10:00', 'loan_checked_in');
        $approved = $this->entryAt('2026-10-05 11:00', 'request_approved');

        $this->assertSame([$checkedIn->id], $this->ids(ActivityLog::filter(['type' => 'loan_checked_in'])));
        $this->assertSame([$created->id, $checkedIn->id], $this->ids(ActivityLog::filter(['type' => 'Loans'])));
        $this->assertSame([$approved->id], $this->ids(ActivityLog::filter(['type' => 'requests'])));
        $this->assertSame([], $this->ids(ActivityLog::filter(['type' => 'nonsense'])));
    }

    public function test_filters_by_equipment_by_user_as_actor_or_subject_by_status_and_by_text(): void
    {
        $admin = $this->admin();
        $mia = $this->borrower();
        $leo = $this->borrower('Leo Cruz');
        $projector = $this->equipment();
        $clicker = $this->equipment('Clicker');

        $lentToMia = ActivityLog::record('loan_created', [
            'actor' => $admin, 'subject' => $mia, 'equipment' => $projector,
            'status_to' => 'Borrowed', 'details' => 'Lent 1 × Projector (Epson) to Mia Santos.',
        ]);
        $miaRequested = ActivityLog::record('request_submitted', [
            'actor' => $mia, 'subject' => $mia, 'equipment' => $clicker,
            'status_to' => 'Pending', 'details' => 'Mia Santos requested 1 × Clicker.',
        ]);
        $leoSuspended = ActivityLog::record('user_suspended', [
            'actor' => $admin, 'subject' => $leo,
            'status_to' => 'Suspended', 'details' => 'Suspended Leo Cruz from borrowing: lost a cable.',
        ]);

        $this->assertSame([$lentToMia->id], $this->ids(ActivityLog::filter(['equipment_id' => $projector->id])));

        // Mia as subject of the loan and as actor and subject of her request.
        $this->assertSame([$lentToMia->id, $miaRequested->id], $this->ids(ActivityLog::filter(['user_id' => $mia->id])));
        // The admin only ever acted.
        $this->assertSame([$lentToMia->id, $leoSuspended->id], $this->ids(ActivityLog::filter(['user_id' => $admin->id])));

        $this->assertSame([$leoSuspended->id], $this->ids(ActivityLog::filter(['status' => 'Suspended'])));

        $this->assertSame([$leoSuspended->id], $this->ids(ActivityLog::filter(['q' => 'lost a cable'])));     // details
        $this->assertSame([$lentToMia->id, $leoSuspended->id], $this->ids(ActivityLog::filter(['q' => 'Quincy'])));    // actor_name
        $this->assertSame([$leoSuspended->id], $this->ids(ActivityLog::filter(['q' => 'Leo Cruz'])));        // subject_name
        $this->assertSame([$miaRequested->id], $this->ids(ActivityLog::filter(['q' => 'Clicker'])));         // equipment_name

        // Filters combine.
        $this->assertSame([$miaRequested->id], $this->ids(ActivityLog::filter(['user_id' => $mia->id, 'type' => 'Requests'])));
    }

    public function test_newest_first_orders_by_when_it_happened_then_by_id(): void
    {
        $old = $this->entryAt('2026-10-01 08:00');
        $sameA = $this->entryAt('2026-10-03 08:00');
        $sameB = $this->entryAt('2026-10-03 08:00');
        $middle = $this->entryAt('2026-10-02 08:00');

        $this->assertSame(
            [$sameB->id, $sameA->id, $middle->id, $old->id],
            ActivityLog::newestFirst()->pluck('id')->all()
        );
    }

    /* ------------------------------------------------------------------
     | activity:backfill
     ------------------------------------------------------------------ */

    /**
     * A small but complete history, each row stamped at a known moment so the
     * test can check the log took its time from the real column.
     */
    private function seedHistory(): array
    {
        $this->at('2026-08-01 08:00:00');
        $admin = $this->admin();
        $mia = $this->borrower();
        $projector = $this->equipment();
        $clicker = $this->equipment('Clicker', 'time_limited');
        $paper = $this->equipment('Bond paper', 'non_returnable');

        // A request approved before decided_at existed: lowercase status, and
        // only updated_at says when.
        $legacy = ItemRequest::create([
            'user_id' => $mia->id, 'equipment_id' => $projector->id, 'quantity' => 1,
            'status' => 'pending', 'requested_date' => '2026-08-01',
        ]);
        $this->at('2026-08-02 15:00:00');
        $legacy->update(['status' => 'approved']);

        // A returnable loan, returned a day late and damaged, resolved, then corrected.
        $this->at('2026-09-09 13:00:00');
        $approved = ItemRequest::create([
            'user_id' => $mia->id, 'equipment_id' => $projector->id, 'quantity' => 2,
            'status' => 'Approved', 'requested_date' => '2026-09-09',
            'decided_at' => Carbon::parse('2026-09-10 07:55:00'), 'decided_by' => $admin->id,
        ]);

        $this->at('2026-09-10 08:00:00');
        $returned = $this->loan($mia, $projector, [
            'borrow_date' => '2026-09-10', 'return_date' => '2026-09-12', 'status' => 'Returned',
        ]);

        $this->at('2026-09-13 10:00:00');
        $log = ReturnLog::create([
            'borrow_transaction_id' => $returned->id, 'return_date' => '2026-09-13',
            'condition' => 'Damaged', 'user_id' => $admin->id,
        ]);
        $this->at('2026-09-14 11:00:00');
        $log->update(['resolution' => 'Replaced the lens cap', 'resolved_at' => now(), 'resolved_by' => $admin->id]);

        $this->at('2026-09-15 12:00:00');
        ReturnLogNote::create(['return_log_id' => $log->id, 'user_id' => $admin->id, 'body' => 'Lens cap was missing too']);

        $this->at('2026-09-20 09:00:00');
        $declined = ItemRequest::create([
            'user_id' => $mia->id, 'equipment_id' => $clicker->id, 'quantity' => 1,
            'status' => 'Declined', 'requested_date' => '2026-09-20',
            'decided_at' => Carbon::parse('2026-09-21 09:30:00'), 'decided_by' => $admin->id,
            'decision_reason' => 'None left this week',
        ]);

        // Users: one each of the states the users table remembers.
        $this->at('2026-09-01 08:00:00');
        $leo = $this->borrower('Leo Cruz');
        $ana = $this->borrower('Ana Lim');
        $carl = $this->borrower('Carl Reyes', 'Instructor');
        $dana = $this->borrower('Dana Uy', 'Instructor');
        $leo->update(['suspended_at' => Carbon::parse('2026-09-26 08:00:00'), 'suspended_by' => $admin->id, 'suspension_reason' => 'Lost a cable']);
        $ana->update(['deactivated_at' => Carbon::parse('2026-09-27 08:00:00')]);
        $carl->update(['role_overridden_at' => Carbon::parse('2026-09-28 08:00:00'), 'role_overridden_by' => $admin->id, 'role_override_reason' => 'Teaches IT 101']);
        $dana->update(['role_overridden_at' => Carbon::parse('2026-09-28 09:00:00'), 'role_overridden_by' => $admin->id, 'role_override_reason' => 'Confirmed instructor request from sign-up']);

        $this->at('2026-09-29 10:00:00');
        $ella = User::factory()->create([
            'user_type' => 'Admin', 'name' => 'Ella Staff',
            'role_overridden_at' => now(), 'role_overridden_by' => $admin->id, 'role_override_reason' => 'Covers the Friday desk',
        ]);

        $retired = $this->equipment('Old VGA cable');
        $retired->update(['retired_at' => Carbon::parse('2026-09-25 08:00:00')]);

        // A timed loan still out, with an automatic and a manual reminder, and
        // a custom message.
        $this->at('2026-10-01 09:00:00');
        $timed = $this->loan($mia, $clicker, [
            'borrow_date' => '2026-10-01 09:00:00', 'return_date' => '2026-10-01 10:00:00', 'timed' => true, 'quantity' => 1,
        ]);
        foreach ([['Return Notice', '2026-10-01 08:00:00'], ['Return Reminder', '2026-10-01 11:00:00'], ['Message from Admin', '2026-10-01 12:00:00']] as [$type, $sent]) {
            Notification::create([
                'user_id' => $mia->id, 'borrow_transaction_id' => $timed->id,
                'message' => 'Please bring it back.', 'notification_type' => $type, 'send_date' => Carbon::parse($sent),
            ]);
        }

        // An issue, later voided.
        $this->at('2026-10-02 14:00:00');
        $issued = $this->loan($mia, $paper, ['return_date' => null, 'status' => 'Issued', 'quantity' => 3]);
        $issued->update(['voided_at' => Carbon::parse('2026-10-03 08:30:00'), 'void_reason' => 'Wrong item']);

        $this->at('2026-10-04 10:00:00');
        $pending = ItemRequest::create([
            'user_id' => $mia->id, 'equipment_id' => $projector->id, 'quantity' => 1,
            'status' => 'Pending', 'requested_date' => '2026-10-04',
        ]);

        $this->at('2026-10-07 09:00:00');

        return compact('admin', 'mia', 'projector', 'returned', 'log', 'legacy', 'approved', 'declined', 'pending',
            'leo', 'ana', 'carl', 'dana', 'ella', 'retired', 'timed', 'issued');
    }

    private function counts(): array
    {
        return ActivityLog::query()->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type')
            ->map(fn ($n) => (int) $n)->sortKeys()->all();
    }

    public function test_backfill_rebuilds_history_from_the_existing_tables(): void
    {
        $h = $this->seedHistory();

        $this->assertSame(0, Artisan::call('activity:backfill'));
        $output = Artisan::output();

        $this->assertSame([
            'equipment_retired' => 1,
            'instructor_confirmed' => 1,
            'loan_checked_in' => 1,
            'loan_created' => 2,
            'loan_issued' => 1,
            'loan_reminder_sent' => 3,
            'loan_voided' => 1,
            'request_approved' => 2,
            'request_declined' => 1,
            'request_submitted' => 4,
            'return_incident_resolved' => 1,
            'return_note_added' => 1,
            'user_created' => 1,
            'user_deactivated' => 1,
            'user_role_changed' => 1,
            'user_suspended' => 1,
        ], $this->counts());
        $this->assertStringContainsString('loan_created', $output);
        $this->assertStringContainsString('Added 23 entries.', $output);

        $this->assertSame(0, ActivityLog::where('source', '!=', 'backfill')->count());
        $this->assertSame(0, ActivityLog::whereNotNull('ip_address')->count());
        $this->assertSame(0, ActivityLog::whereNull('backfill_key')->count());

        $byKey = fn (string $key) => ActivityLog::where('backfill_key', $key)->firstOrFail();

        // Who recorded a loan was never stored.
        $created = $byKey('loan_created:borrow_transactions:'.$h['returned']->id);
        $this->assertSame('2026-09-10 08:00:00', $created->occurred_at->toDateTimeString());
        $this->assertNull($created->actor_id);
        $this->assertSame('Not recorded', $created->actor_name);
        $this->assertSame(['Mia Santos', 'Projector (Epson)', 2, 'Borrowed'],
            [$created->subject_name, $created->equipment_name, $created->quantity, $created->status_to]);
        $this->assertStringContainsString('due Sep 12, 2026', $created->details);

        $this->assertStringContainsString('due Oct 1, 2026, 10:00 AM',
            $byKey('loan_created:borrow_transactions:'.$h['timed']->id)->details);

        // The receiver checked it in, a day after it was due.
        $checkIn = $byKey('loan_checked_in:return_logs:'.$h['log']->id);
        $this->assertSame('2026-09-13 10:00:00', $checkIn->occurred_at->toDateTimeString());
        $this->assertSame([$h['admin']->id, 'Quincy Jane Oliver', 'Admin'], [$checkIn->actor_id, $checkIn->actor_name, $checkIn->actor_role]);
        $this->assertSame(['Overdue', 'Returned'], [$checkIn->status_from, $checkIn->status_to]);
        $this->assertSame($h['returned']->id, $checkIn->borrow_transaction_id);
        $this->assertSame('Damaged', $checkIn->meta['condition']);

        $resolved = $byKey('return_incident_resolved:return_logs:'.$h['log']->id);
        $this->assertSame(['2026-09-14 11:00:00', 'Quincy Jane Oliver'], [$resolved->occurred_at->toDateTimeString(), $resolved->actor_name]);
        $this->assertStringContainsString('Replaced the lens cap', $resolved->details);

        $note = ActivityLog::where('type', 'return_note_added')->sole();
        $this->assertSame('2026-09-15 12:00:00', $note->occurred_at->toDateTimeString());
        $this->assertStringContainsString('Lens cap was missing too', $note->details);

        // The issue, then its void from the status it held.
        $issued = $byKey('loan_issued:borrow_transactions:'.$h['issued']->id);
        $this->assertSame('Issued', $issued->status_to);
        $void = $byKey('loan_voided:borrow_transactions:'.$h['issued']->id);
        $this->assertSame(['2026-10-03 08:30:00', 'Issued', 'Void'], [$void->occurred_at->toDateTimeString(), $void->status_from, $void->status_to]);
        $this->assertStringContainsString('Wrong item', $void->details);

        // The borrower submitted; the admin decided.
        $submitted = $byKey('request_submitted:item_requests:'.$h['pending']->id);
        $this->assertSame(['Mia Santos', 'Student', 'Pending'], [$submitted->actor_name, $submitted->actor_role, $submitted->status_to]);

        $approved = $byKey('request_approved:item_requests:'.$h['approved']->id);
        $this->assertSame(['2026-09-10 07:55:00', 'Quincy Jane Oliver', 'Pending', 'Approved'],
            [$approved->occurred_at->toDateTimeString(), $approved->actor_name, $approved->status_from, $approved->status_to]);

        $legacy = $byKey('request_approved:item_requests:'.$h['legacy']->id);
        $this->assertSame(['2026-08-02 15:00:00', 'Not recorded', 'updated_at'],
            [$legacy->occurred_at->toDateTimeString(), $legacy->actor_name, $legacy->meta['occurred_at_from']]);

        $declined = $byKey('request_declined:item_requests:'.$h['declined']->id);
        $this->assertStringContainsString('None left this week', $declined->details);

        // Users and equipment.
        $this->assertSame('Quincy Jane Oliver', ActivityLog::where('type', 'user_suspended')->sole()->actor_name);
        $this->assertSame('Not recorded', ActivityLog::where('type', 'user_deactivated')->sole()->actor_name);
        $this->assertSame([$h['carl']->id, 'Instructor'], [
            ActivityLog::where('type', 'user_role_changed')->sole()->subject_user_id,
            ActivityLog::where('type', 'user_role_changed')->sole()->status_to,
        ]);
        $this->assertSame($h['dana']->id, ActivityLog::where('type', 'instructor_confirmed')->sole()->subject_user_id);
        $this->assertSame([$h['ella']->id, 'Admin'], [
            ActivityLog::where('type', 'user_created')->sole()->subject_user_id,
            ActivityLog::where('type', 'user_created')->sole()->status_to,
        ]);
        $retired = ActivityLog::where('type', 'equipment_retired')->sole();
        $this->assertSame(['Old VGA cable', '2026-09-25 08:00:00'], [$retired->equipment_name, $retired->occurred_at->toDateTimeString()]);

        // Both reminders and the custom message, as the loans screen logs them live.
        $this->assertEqualsCanonicalizing(['Return Notice', 'Return Reminder', 'Message from Admin'],
            ActivityLog::where('type', 'loan_reminder_sent')->get()->pluck('meta.notification_type')->all());
    }

    public function test_a_second_backfill_adds_nothing(): void
    {
        $this->seedHistory();

        Artisan::call('activity:backfill');
        $first = $this->counts();

        $this->assertSame(0, Artisan::call('activity:backfill'));

        $this->assertSame($first, $this->counts());
        $this->assertStringContainsString('No new entries', Artisan::output());
    }

    public function test_a_new_suspension_after_a_backfill_is_added_on_the_next_run_and_the_old_one_stays(): void
    {
        $h = $this->seedHistory();
        Artisan::call('activity:backfill');

        // Lifted, then suspended again: the users row only remembers the latest.
        $h['leo']->update(['suspended_at' => Carbon::parse('2026-10-06 08:00:00'), 'suspension_reason' => 'Late again']);
        Artisan::call('activity:backfill');

        $this->assertSame(
            ['2026-09-26 08:00:00', '2026-10-06 08:00:00'],
            ActivityLog::where('type', 'user_suspended')->orderBy('occurred_at')->get()
                ->map(fn ($log) => $log->occurred_at->toDateTimeString())->all()
        );
    }

    public function test_the_backfill_stops_where_the_live_log_of_a_type_begins(): void
    {
        $admin = $this->admin();
        $mia = $this->borrower();
        $projector = $this->equipment();

        $this->at('2026-10-01 09:00:00');
        $before = $this->loan($mia, $projector);

        // Live logging of loans began with this one, and wrote its own entry.
        $this->at('2026-10-06 09:00:00');
        $after = $this->loan($mia, $projector);
        ActivityLog::record('loan_created', ['actor' => $admin, 'loan' => $after]);

        Artisan::call('activity:backfill');

        $this->assertSame(
            [[$before->id, 'backfill'], [$after->id, 'live']],
            ActivityLog::where('type', 'loan_created')->orderBy('borrow_transaction_id')->get()
                ->map(fn ($log) => [$log->borrow_transaction_id, $log->source])->all()
        );
        $this->assertStringContainsString('Covered by live log', Artisan::output());
    }
}
