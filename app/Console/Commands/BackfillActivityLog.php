<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\ReturnLogNote;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild the activity log from what the older tables already recorded.
 *
 * Run with: php artisan activity:backfill
 *
 * Every entry it writes has source 'backfill' and a backfill_key made from the
 * row it came from ("loan_created:borrow_transactions:12"), so a second run
 * finds the key and adds nothing. Keys for state that can be set again (a
 * suspension, a role change, a retirement) include the timestamp, so a new
 * suspension after a lift is a new entry and the old one stays.
 *
 * It only reaches as far as the live log. Once live entries of a type exist,
 * the backfill skips that type from the first live entry onwards, so the same
 * event is never written twice, once from each side.
 *
 * What the old schema cannot tell it, it does not invent: where nobody stored
 * who acted, the actor is "Not recorded". Some history is gone for good:
 * a lifted suspension, a reactivated account, a restored item, a declined
 * instructor request, and every role change but the latest leave no trace.
 */
class BackfillActivityLog extends Command
{
    protected $signature = 'activity:backfill';

    protected $description = 'Rebuild the activity log from existing loans, returns, requests, users and reminders (safe to run again)';

    /** The reason UserController::confirmInstructor writes. */
    private const INSTRUCTOR_CONFIRMED_REASON = 'Confirmed instructor request from sign-up';

    /**
     * Notification types logged as loan_reminder_sent: the nightly sweep's,
     * the manual send's two canned ones, and its custom message — the same
     * set BorrowTransactionController logs live, so the two sides agree.
     */
    private const REMINDER_TYPES = ['Return Notice', 'Return Reminder', 'Overdue notice', 'Message from Admin'];

    /** @var array<string, array{added: int, kept: int, live: int}> */
    private array $counts = [];

    /** @var array<string, CarbonInterface> type => the first live entry's time */
    private array $liveSince = [];

    public function handle(): int
    {
        // Artisan keeps one instance per process, so a second call in the same
        // run (a test, a scheduler loop) must not inherit the first one's tally.
        $this->counts = [];
        $this->liveSince = ActivityLog::where('source', 'live')
            ->groupBy('type')
            ->selectRaw('type, MIN(occurred_at) as first_at')
            ->pluck('first_at', 'type')
            ->map(fn ($at) => Carbon::parse($at))
            ->all();

        $this->loans();
        $this->checkIns();
        $this->voids();
        $this->requests();
        $this->retirements();
        $this->users();
        $this->resolutions();
        $this->notes();
        $this->reminders();

        $this->report();

        return self::SUCCESS;
    }

    /* ---------------------------------------------------------------------
     | Sources
     --------------------------------------------------------------------- */

    /** loan_created, or loan_issued for a non-returnable hand-over. Who recorded it was never stored. */
    private function loans(): void
    {
        BorrowTransaction::with(['user', 'equipment'])->chunkById(200, function (Collection $loans) {
            $this->write($loans->map(function (BorrowTransaction $loan) {
                $issued = $loan->status === 'Issued';
                $type = $issued ? 'loan_issued' : 'loan_created';

                return $this->entry($type, 'borrow_transactions', $loan->id, [
                    'occurred_at' => $loan->created_at ?? $loan->borrow_date,
                    'actor' => null,
                    'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
                    'loan' => $loan,
                    'quantity' => $loan->quantity,
                    'status_to' => $issued ? 'Issued' : 'Borrowed',
                    'details' => $issued
                        ? 'Issued '.$this->units($loan).' to '.$this->name($loan->user).', not expected back.'
                        : 'Lent '.$this->units($loan).' to '.$this->name($loan->user).', '.$this->dueWords($loan).'.',
                    'meta' => array_filter([
                        'borrow_date' => $loan->borrow_date?->toDateTimeString(),
                        'return_date' => $loan->return_date?->toDateTimeString(),
                        'timed' => $loan->timed,
                        'purpose' => $loan->purpose,
                    ], fn ($value) => $value !== null),
                ]);
            }));
        });
    }

    /** loan_checked_in from each return log; the receiver is the actor. */
    private function checkIns(): void
    {
        ReturnLog::with(['receiver', 'borrowTransaction.user', 'borrowTransaction.equipment'])
            ->chunkById(200, function (Collection $logs) {
                $this->write($logs->map(function (ReturnLog $log) {
                    $loan = $log->borrowTransaction;
                    // A timed loan is judged by when the log was written, since
                    // return_logs.return_date holds no time of day.
                    $returnedAt = $loan?->timed ? $log->created_at : $log->return_date;
                    $late = $loan?->dueAt() && $returnedAt?->gt($loan->dueAt());

                    return $this->entry('loan_checked_in', 'return_logs', $log->id, [
                        'occurred_at' => $log->created_at ?? $log->return_date,
                        ...$this->actor($log->receiver),
                        'loan' => $loan,
                        'quantity' => $loan?->quantity,
                        'status_from' => $late ? 'Overdue' : 'Borrowed',
                        'status_to' => 'Returned',
                        'details' => 'Checked in '.$this->units($loan).' from '.$this->name($loan?->user)
                            .' in '.$log->condition.' condition, '.($late ? 'late' : 'on time').'.',
                        'meta' => array_filter([
                            'condition' => $log->condition,
                            'remarks' => $log->remarks,
                            'return_date' => $log->return_date?->toDateString(),
                        ], fn ($value) => $value !== null),
                    ]);
                }));
            });
    }

    /** loan_voided from voided_at. Who voided it was never stored. */
    private function voids(): void
    {
        BorrowTransaction::with(['user', 'equipment'])->whereNotNull('voided_at')
            ->chunkById(200, function (Collection $loans) {
                $this->write($loans->map(fn (BorrowTransaction $loan) => $this->entry('loan_voided', 'borrow_transactions', $loan->id, [
                    'occurred_at' => $loan->voided_at,
                    'actor' => null,
                    'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
                    'loan' => $loan,
                    'quantity' => $loan->quantity,
                    // The status stays as it was when a loan is voided, so the
                    // stored one is the status it was voided from.
                    'status_from' => $loan->status,
                    'status_to' => 'Void',
                    'details' => 'Voided the record of '.$this->units($loan).' to '.$this->name($loan->user)
                        .($loan->void_reason ? ': '.$loan->void_reason : '').'.',
                    'meta' => array_filter(['void_reason' => $loan->void_reason]),
                ])));
            });
    }

    /**
     * request_submitted for every request (the borrower is the actor), then
     * request_approved / request_declined for the decided ones. A decision
     * made before decided_at existed falls back to updated_at, the same
     * fallback ItemRequest::decisionLine() uses, and says so in meta.
     */
    private function requests(): void
    {
        ItemRequest::with(['user', 'equipment', 'decider'])->chunkById(200, function (Collection $requests) {
            $this->write($requests->map(fn (ItemRequest $request) => $this->entry('request_submitted', 'item_requests', $request->id, [
                'occurred_at' => $request->created_at ?? $request->requested_date,
                ...$this->actor($request->user),
                'subject' => $request->user,
                'equipment' => $request->equipment,
                'quantity' => $request->quantity,
                'status_to' => 'Pending',
                'details' => $this->name($request->user).' requested '.$request->quantity.' × '.$this->item($request->equipment).'.',
                'meta' => array_filter(['remarks' => $request->remarks]),
            ])));

            $this->write($requests->map(function (ItemRequest $request) {
                $status = strtolower((string) $request->status);
                $type = match ($status) {
                    'approved' => 'request_approved',
                    'declined', 'denied' => 'request_declined',
                    default => null,
                };

                if ($type === null) {
                    return null;
                }

                $verb = $type === 'request_approved' ? 'Approved' : 'Declined';

                return $this->entry($type, 'item_requests', $request->id, [
                    'occurred_at' => $request->decided_at ?? $request->updated_at,
                    ...$this->actor($request->decider),
                    'subject' => $request->user,
                    'equipment' => $request->equipment,
                    'quantity' => $request->quantity,
                    'status_from' => 'Pending',
                    'status_to' => $verb,
                    'details' => $verb.' a request from '.$this->name($request->user).' for '
                        .$request->quantity.' × '.$this->item($request->equipment)
                        .($request->decision_reason ? ': '.$request->decision_reason : '').'.',
                    'meta' => array_filter([
                        'decision_reason' => $request->decision_reason,
                        'occurred_at_from' => $request->decided_at ? null : 'updated_at',
                    ]),
                ]);
            })->filter());
        });
    }

    /** equipment_retired for items retired now; a restore clears retired_at, so earlier retirements are gone. */
    private function retirements(): void
    {
        Equipment::whereNotNull('retired_at')->chunkById(200, function (Collection $items) {
            $this->write($items->map(fn (Equipment $item) => $this->entry('equipment_retired', 'equipment', $item->id, [
                'occurred_at' => $item->retired_at,
                'actor' => null,
                'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
                'equipment' => $item,
                'status_to' => 'Retired',
                'details' => 'Retired '.$item->equipment_name.' from lending.',
            ], $item->retired_at)));
        });
    }

    /**
     * user_deactivated, user_suspended, and the latest role record. A role
     * record is one of three things: an instructor confirmation (by its
     * reason), an admin account created with a reason (stamped within a minute
     * of the account's creation), or a role change.
     */
    private function users(): void
    {
        User::with(['suspender', 'roleOverrider'])->chunkById(200, function (Collection $users) {
            $this->write($users->filter(fn (User $user) => $user->deactivated_at)->map(fn (User $user) => $this->entry('user_deactivated', 'users', $user->id, [
                'occurred_at' => $user->deactivated_at,
                'actor' => null,
                'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
                'subject' => $user,
                'status_to' => 'Deactivated',
                'details' => 'Deactivated the account of '.$user->name.'.',
            ], $user->deactivated_at)));

            $this->write($users->filter(fn (User $user) => $user->suspended_at)->map(fn (User $user) => $this->entry('user_suspended', 'users', $user->id, [
                'occurred_at' => $user->suspended_at,
                ...$this->actor($user->suspender),
                'subject' => $user,
                'status_to' => 'Suspended',
                'details' => 'Suspended '.$user->name.' from borrowing'
                    .($user->suspension_reason ? ': '.$user->suspension_reason : '').'.',
                'meta' => array_filter(['reason' => $user->suspension_reason]),
            ], $user->suspended_at)));

            $this->write($users->filter(fn (User $user) => $user->role_overridden_at)->map(function (User $user) {
                $reason = $user->role_override_reason;
                $at = $user->role_overridden_at;

                [$type, $from, $details] = match (true) {
                    $reason === self::INSTRUCTOR_CONFIRMED_REASON => [
                        'instructor_confirmed', 'Student',
                        'Confirmed '.$user->name.' as an instructor, as requested at sign-up.',
                    ],
                    $user->user_type === 'Admin' && $user->created_at
                        && abs($at->diffInSeconds($user->created_at)) <= 60 => [
                            'user_created', null,
                            'Created an admin account for '.$user->name.($reason ? ': '.$reason : '').'.',
                        ],
                    default => [
                        'user_role_changed', null,
                        'Changed the role of '.$user->name.' to '.$user->user_type.($reason ? ': '.$reason : '').'.',
                    ],
                };

                return $this->entry($type, 'users', $user->id, [
                    'occurred_at' => $at,
                    ...$this->actor($user->roleOverrider),
                    'subject' => $user,
                    'status_from' => $from,
                    // The record is the latest change, so the current role is
                    // the one it changed to.
                    'status_to' => $user->user_type,
                    'details' => $details,
                    'meta' => array_filter(['reason' => $reason]),
                ], $at);
            }));
        });
    }

    /** return_incident_resolved from return_logs.resolved_*. */
    private function resolutions(): void
    {
        ReturnLog::with(['resolver', 'borrowTransaction.user', 'borrowTransaction.equipment'])
            ->whereNotNull('resolved_at')
            ->chunkById(200, function (Collection $logs) {
                $this->write($logs->map(fn (ReturnLog $log) => $this->entry('return_incident_resolved', 'return_logs', $log->id, [
                    'occurred_at' => $log->resolved_at,
                    ...$this->actor($log->resolver),
                    'loan' => $log->borrowTransaction,
                    'quantity' => $log->borrowTransaction?->quantity,
                    'status_to' => 'Resolved',
                    'details' => 'Recorded the outcome of a '.strtolower($log->condition).' return of '
                        .$this->item($log->borrowTransaction?->equipment).': '.$log->resolution,
                    'meta' => ['condition' => $log->condition, 'resolution' => $log->resolution],
                ])));
            });
    }

    /** return_note_added from return_log_notes; the note's author is the actor. */
    private function notes(): void
    {
        ReturnLogNote::with(['author', 'returnLog.borrowTransaction.user', 'returnLog.borrowTransaction.equipment'])
            ->chunkById(200, function (Collection $notes) {
                $this->write($notes->map(function (ReturnLogNote $note) {
                    $loan = $note->returnLog?->borrowTransaction;

                    return $this->entry('return_note_added', 'return_log_notes', $note->id, [
                        'occurred_at' => $note->created_at,
                        ...$this->actor($note->author),
                        'loan' => $loan,
                        'details' => 'Correction to the return of '.$this->item($loan?->equipment)
                            .' from '.$this->name($loan?->user).': '.$note->body,
                        'meta' => ['return_log_id' => $note->return_log_id],
                    ]);
                }));
            });
    }

    /**
     * loan_reminder_sent from the notifications the loans screen and the
     * nightly sweep write: "Return Notice", "Return Reminder", "Overdue
     * notice", and a custom "Message from Admin". Neither path stored who
     * sent it.
     */
    private function reminders(): void
    {
        Notification::with(['user', 'borrowTransaction.equipment'])
            ->whereIn('notification_type', self::REMINDER_TYPES)
            ->chunkById(200, function (Collection $notifications) {
                $this->write($notifications->map(function (Notification $notification) {
                    $loan = $notification->borrowTransaction;

                    return $this->entry('loan_reminder_sent', 'notifications', $notification->id, [
                        'occurred_at' => $notification->send_date ?? $notification->created_at,
                        'actor' => null,
                        'actor_name' => ActivityLog::ACTOR_NOT_RECORDED,
                        'loan' => $loan,
                        'subject' => $notification->user,
                        'details' => $notification->notification_type.' sent to '.$this->name($notification->user)
                            .($loan ? ' about '.$this->units($loan) : '').'.',
                        'meta' => [
                            'notification_type' => $notification->notification_type,
                            'message' => $notification->message,
                        ],
                    ]);
                }));
            });
    }

    /* ---------------------------------------------------------------------
     | Writing
     --------------------------------------------------------------------- */

    /**
     * One candidate entry. The key names the source row, plus the time for
     * state that can be set more than once.
     */
    private function entry(string $type, string $table, int $id, array $attrs, ?CarbonInterface $at = null): array
    {
        $key = $type.':'.$table.':'.$id.($at ? ':'.$at->format('YmdHis') : '');

        return ['type' => $type, 'key' => $key, 'attrs' => $attrs];
    }

    /** Write the entries not written before, in one transaction per chunk. */
    private function write(Collection $entries): void
    {
        $entries = $entries->filter(fn (array $entry) => $entry['attrs']['occurred_at'] !== null)->values();

        if ($entries->isEmpty()) {
            return;
        }

        $existing = ActivityLog::whereIn('backfill_key', $entries->pluck('key'))
            ->pluck('backfill_key')
            ->flip();

        DB::transaction(function () use ($entries, $existing) {
            foreach ($entries as $entry) {
                $type = $entry['type'];
                $this->counts[$type] ??= ['added' => 0, 'kept' => 0, 'live' => 0];

                if (isset($existing[$entry['key']])) {
                    $this->counts[$type]['kept']++;

                    continue;
                }

                $liveSince = $this->liveSince[$type] ?? null;
                if ($liveSince && $entry['attrs']['occurred_at']->gte($liveSince)) {
                    $this->counts[$type]['live']++;

                    continue;
                }

                ActivityLog::record($type, $entry['attrs'] + [
                    'source' => 'backfill',
                    'backfill_key' => $entry['key'],
                    'ip_address' => null,
                ]);
                $this->counts[$type]['added']++;
            }
        });
    }

    private function report(): void
    {
        if ($this->counts === []) {
            $this->info('Nothing to backfill: the source tables are empty.');

            return;
        }

        $order = array_flip(array_keys(ActivityLog::TYPES));
        uksort($this->counts, fn ($a, $b) => $order[$a] <=> $order[$b]);

        $this->table(
            ['Type', 'Added', 'Already logged', 'Covered by live log'],
            collect($this->counts)->map(fn ($count, $type) => [
                $type, $count['added'], $count['kept'], $count['live'],
            ])->values()->all()
        );

        $added = array_sum(array_column($this->counts, 'added'));
        $this->info($added === 0 ? 'No new entries — the log was already up to date.' : "Added {$added} entries.");
    }

    /* ---------------------------------------------------------------------
     | Wording
     --------------------------------------------------------------------- */

    /** The actor, or "Not recorded" when the old row stored nobody. */
    private function actor(?User $user): array
    {
        return $user
            ? ['actor' => $user]
            : ['actor' => null, 'actor_name' => ActivityLog::ACTOR_NOT_RECORDED];
    }

    private function name(?User $user): string
    {
        return $user?->name ?? 'a deleted account';
    }

    private function item(?Equipment $equipment): string
    {
        return $equipment?->equipment_name ?? 'a deleted item';
    }

    /** "2 × Projector (Epson)" — the wording the live entries use. */
    private function units(?BorrowTransaction $loan): string
    {
        return ActivityLog::units($loan);
    }

    private function dueWords(BorrowTransaction $loan): string
    {
        return ActivityLog::dueWords($loan);
    }
}
