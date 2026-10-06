<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * One entry in the audit trail.
 *
 * Append-only, in the same spirit as the return logs: a row is written once and
 * never changed or removed. Updating or deleting one throws, whether through the
 * model or a query. A wrong entry is answered by a later one. Only a raw
 * `DB::table()` call or the foreign keys going null can still touch a row, and
 * the second is deliberate: the name snapshots keep the history readable.
 *
 * Write entries with record(), inside the same DB transaction as the change
 * they describe, so a rolled-back change leaves no entry behind.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'occurred_at', 'type',
        'actor_id', 'actor_name', 'actor_role',
        'subject_user_id', 'subject_name',
        'equipment_id', 'equipment_name',
        'borrow_transaction_id', 'quantity',
        'status_from', 'status_to',
        'details', 'meta', 'ip_address', 'source', 'backfill_key',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity' => 'integer',
            'meta' => 'array',
        ];
    }

    /** Groups in the order a report lists them. */
    public const GROUPS = ['Loans', 'Requests', 'Equipment', 'Users', 'Returns', 'Account & system'];

    /** What the backfill writes as the actor where the old schema never stored who. */
    public const ACTOR_NOT_RECORDED = 'Not recorded';

    /**
     * Every event type. The key is what is stored, so never rename one; the
     * label is what a screen shows and can change freely.
     */
    public const TYPES = [
        'loan_created' => ['label' => 'Loan recorded', 'group' => 'Loans'],
        'loan_issued' => ['label' => 'Item issued', 'group' => 'Loans'],
        'loan_updated' => ['label' => 'Loan edited', 'group' => 'Loans'],
        'loan_checked_in' => ['label' => 'Checked in', 'group' => 'Loans'],
        'loan_voided' => ['label' => 'Loan voided', 'group' => 'Loans'],
        'loan_deleted' => ['label' => 'Loan deleted', 'group' => 'Loans'],
        'loan_marked_overdue' => ['label' => 'Marked overdue', 'group' => 'Loans'],
        'loan_reminder_sent' => ['label' => 'Reminder sent', 'group' => 'Loans'],

        'request_submitted' => ['label' => 'Request submitted', 'group' => 'Requests'],
        'request_updated' => ['label' => 'Request changed', 'group' => 'Requests'],
        'request_cancelled' => ['label' => 'Request cancelled', 'group' => 'Requests'],
        'request_approved' => ['label' => 'Request approved', 'group' => 'Requests'],
        'request_declined' => ['label' => 'Request declined', 'group' => 'Requests'],

        'equipment_added' => ['label' => 'Item added', 'group' => 'Equipment'],
        'equipment_updated' => ['label' => 'Item edited', 'group' => 'Equipment'],
        'equipment_retired' => ['label' => 'Item retired', 'group' => 'Equipment'],
        'equipment_restored' => ['label' => 'Item restored', 'group' => 'Equipment'],
        'equipment_deleted' => ['label' => 'Item deleted', 'group' => 'Equipment'],
        'equipment_status_changed' => ['label' => 'Availability changed', 'group' => 'Equipment'],
        'maintenance_logged' => ['label' => 'Maintenance logged', 'group' => 'Equipment'],
        'repair_logged' => ['label' => 'Repair logged', 'group' => 'Equipment'],

        'user_created' => ['label' => 'Account created', 'group' => 'Users'],
        'user_updated' => ['label' => 'Account edited', 'group' => 'Users'],
        'user_role_changed' => ['label' => 'Role changed', 'group' => 'Users'],
        'user_deactivated' => ['label' => 'Account deactivated', 'group' => 'Users'],
        'user_reactivated' => ['label' => 'Account reactivated', 'group' => 'Users'],
        'user_suspended' => ['label' => 'Borrowing suspended', 'group' => 'Users'],
        'suspension_lifted' => ['label' => 'Suspension lifted', 'group' => 'Users'],
        'user_deleted' => ['label' => 'Account deleted', 'group' => 'Users'],
        'instructor_confirmed' => ['label' => 'Instructor confirmed', 'group' => 'Users'],
        'instructor_declined' => ['label' => 'Instructor request declined', 'group' => 'Users'],
        'schedule_added' => ['label' => 'Class schedule added', 'group' => 'Users'],
        'schedule_updated' => ['label' => 'Class schedule edited', 'group' => 'Users'],
        'schedule_deleted' => ['label' => 'Class schedule removed', 'group' => 'Users'],

        'return_incident_resolved' => ['label' => 'Incident resolved', 'group' => 'Returns'],
        'return_note_added' => ['label' => 'Correction added', 'group' => 'Returns'],

        'login' => ['label' => 'Signed in', 'group' => 'Account & system'],
        'login_failed' => ['label' => 'Sign-in failed', 'group' => 'Account & system'],
        'logout' => ['label' => 'Signed out', 'group' => 'Account & system'],
        'password_reset_requested' => ['label' => 'Password reset requested', 'group' => 'Account & system'],
        'password_reset_completed' => ['label' => 'Password reset', 'group' => 'Account & system'],
        'report_exported' => ['label' => 'Report exported', 'group' => 'Account & system'],
    ];

    /* ---------------------------------------------------------------------
     | Writing
     --------------------------------------------------------------------- */

    /**
     * Write one entry, now.
     *
     * $attrs takes any column, plus four models whose names are snapshotted:
     *  - 'actor': the User who did it. Defaults to the signed-in user; pass
     *    `'actor' => null` for the system (the scheduler).
     *  - 'subject': the User it happened to.
     *  - 'equipment': the Equipment it was about.
     *  - 'loan': the BorrowTransaction. Its item and borrower fill 'equipment'
     *    and 'subject' when those are not given.
     *
     * A column passed explicitly wins over a snapshot. The backfill uses that
     * to set occurred_at to the real time and actor_name to "Not recorded".
     */
    public static function record(string $type, array $attrs = []): self
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Unknown activity type [{$type}].");
        }

        $loan = $attrs['loan'] ?? null;
        $equipment = $attrs['equipment'] ?? $loan?->equipment;
        $subject = $attrs['subject'] ?? $loan?->user;
        $actor = array_key_exists('actor', $attrs) ? $attrs['actor'] : auth()->user();

        unset($attrs['loan'], $attrs['equipment'], $attrs['subject'], $attrs['actor']);

        $log = new self(array_merge([
            'occurred_at' => now(),
            'type' => $type,
            'actor_id' => $actor?->id,
            'actor_name' => self::fit($actor?->name, 120),
            'actor_role' => self::fit($actor?->user_type, 20),
            'subject_user_id' => $subject?->id,
            'subject_name' => self::fit($subject?->name, 255),
            'equipment_id' => $equipment?->id,
            'equipment_name' => self::fit($equipment?->equipment_name, 255),
            'borrow_transaction_id' => $loan?->id,
            'ip_address' => self::requestIp(),
            'source' => 'live',
        ], $attrs));

        $log->save();

        return $log;
    }

    /**
     * The address of the web request being served. A console run (the
     * scheduler, artisan) gets a synthetic request that always says 127.0.0.1,
     * which is not where anything came from, so it records none.
     */
    private static function requestIp(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return request()?->ip();
    }

    /** A snapshot cut to its column, so a long name cannot fail the write. */
    private static function fit(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }

    /* ---------------------------------------------------------------------
     | Append-only
     --------------------------------------------------------------------- */

    protected static function booted(): void
    {
        // Covers update(), save() on a loaded row, touch(), increment() and
        // delete() on a model instance.
        static::updating(fn () => throw self::appendOnly('changed'));
        static::deleting(fn () => throw self::appendOnly('deleted'));
    }

    public static function appendOnly(string $what): LogicException
    {
        return new LogicException("Activity log entries are append-only and cannot be {$what}.");
    }

    /**
     * The same refusal for query writes such as `ActivityLog::where(...)->delete()`,
     * which never load a model and so never fire the events above.
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function update(array $values)
            {
                throw ActivityLog::appendOnly('changed');
            }

            public function upsert(array $values, $uniqueBy, $update = null)
            {
                throw ActivityLog::appendOnly('changed');
            }

            public function increment($column, $amount = 1, array $extra = [])
            {
                throw ActivityLog::appendOnly('changed');
            }

            public function decrement($column, $amount = 1, array $extra = [])
            {
                throw ActivityLog::appendOnly('changed');
            }

            public function touch($column = null)
            {
                throw ActivityLog::appendOnly('changed');
            }

            public function delete()
            {
                throw ActivityLog::appendOnly('deleted');
            }

            public function forceDelete()
            {
                throw ActivityLog::appendOnly('deleted');
            }
        };
    }

    /* ---------------------------------------------------------------------
     | Reading
     --------------------------------------------------------------------- */

    public function typeLabel(): string
    {
        return self::TYPES[$this->type]['label'] ?? Str::headline((string) $this->type);
    }

    public function groupLabel(): string
    {
        return self::TYPES[$this->type]['group'] ?? 'Other';
    }

    /** The type keys in one group, in TYPES order. */
    public static function typesInGroup(string $group): array
    {
        return array_keys(array_filter(
            self::TYPES,
            fn (array $type) => strcasecmp($type['group'], $group) === 0
        ));
    }

    /** "Quincy Jane Oliver", "Not recorded", or "System" when no person was involved. */
    public function actorLabel(): string
    {
        return $this->actor_name ?? 'System';
    }

    /**
     * Report filters. Every key is optional and a blank value is ignored:
     *  - from / to: dates, inclusive; `to` covers the whole of its day in the
     *    app timezone. An unreadable date is ignored rather than failing;
     *  - type: a type key, or a group name from GROUPS (any case). Anything
     *    else matches nothing;
     *  - equipment_id;
     *  - user_id: the person as actor or as subject;
     *  - status: matches status_to, the status after the event;
     *  - q: a substring of details, actor_name, subject_name or equipment_name.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if ($from = self::day($filters['from'] ?? null)) {
            $query->where('occurred_at', '>=', $from->startOfDay());
        }

        if ($to = self::day($filters['to'] ?? null)) {
            $query->where('occurred_at', '<', $to->addDay()->startOfDay());
        }

        if (is_string($type = $filters['type'] ?? null) && $type !== '') {
            $query->whereIn('type', isset(self::TYPES[$type]) ? [$type] : self::typesInGroup($type));
        }

        if (filled($equipmentId = $filters['equipment_id'] ?? null)) {
            $query->where('equipment_id', $equipmentId);
        }

        if (filled($userId = $filters['user_id'] ?? null)) {
            $query->where(fn (Builder $q) => $q
                ->where('actor_id', $userId)
                ->orWhere('subject_user_id', $userId));
        }

        if (filled($status = $filters['status'] ?? null)) {
            $query->where('status_to', $status);
        }

        if (filled($search = trim((string) ($filters['q'] ?? '')))) {
            $like = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->where('details', 'like', $like)
                ->orWhere('actor_name', 'like', $like)
                ->orWhere('subject_name', 'like', $like)
                ->orWhere('equipment_name', 'like', $like));
        }

        return $query;
    }

    /** Newest first; id breaks ties between entries written in the same second. */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /** A filter date in the app timezone, or null when blank or unreadable. */
    private static function day(mixed $value): ?CarbonInterface
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /* ---------------------------------------------------------------------
     | Relationships — all nullable, the snapshots outlive them
     --------------------------------------------------------------------- */

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }
}
