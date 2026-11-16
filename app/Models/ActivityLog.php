<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

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

    public const GROUPS = ['Loans', 'Requests', 'Equipment', 'Users', 'Returns', 'Account & system'];

    public const GROUP_LABELS = ['Loans' => 'Borrows'];

    public const ACTOR_NOT_RECORDED = 'Not recorded';

    public const ACTOR_ANONYMOUS = 'Not signed in';

    public const TYPES = [
        'loan_created' => ['label' => 'Borrow recorded', 'group' => 'Loans'],
        'loan_issued' => ['label' => 'Item issued', 'group' => 'Loans'],
        'loan_updated' => ['label' => 'Borrow edited', 'group' => 'Loans'],
        'loan_checked_in' => ['label' => 'Checked in', 'group' => 'Loans'],
        'loan_voided' => ['label' => 'Borrow voided', 'group' => 'Loans'],
        'loan_deleted' => ['label' => 'Borrow deleted', 'group' => 'Loans'],
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

    public static function recordHandover(BorrowTransaction $loan, array $attrs = []): self
    {
        $issued = $loan->status === 'Issued';
        $borrower = $loan->user?->name ?? 'a deleted account';
        $extraMeta = $attrs['meta'] ?? [];
        unset($attrs['meta']);

        $entry = array_merge([
            'loan' => $loan,
            'quantity' => $loan->quantity,
            'status_to' => $issued ? 'Issued' : 'Borrowed',
            'details' => $issued
                ? 'Issued '.self::units($loan).' to '.$borrower.', not expected back.'
                : 'Lent '.self::units($loan).' to '.$borrower.', '.self::dueWords($loan).'.',
            'meta' => array_filter([
                'borrow_date' => $loan->borrow_date?->toDateTimeString(),
                'return_date' => $loan->return_date?->toDateTimeString(),
                'timed' => $loan->timed,
                'purpose' => $loan->purpose,
                'class_schedule_id' => $loan->class_schedule_id,
            ], fn ($value) => $value !== null),
        ], $attrs);
        $entry['meta'] += $extraMeta;

        return self::record($issued ? 'loan_issued' : 'loan_created', $entry);
    }

    private static function requestIp(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return request()?->ip();
    }

    public static function units(?BorrowTransaction $loan): string
    {
        return $loan
            ? $loan->quantity.' × '.($loan->equipment?->equipment_name ?? 'a deleted item')
            : 'a deleted borrow';
    }

    public static function dueWords(BorrowTransaction $loan): string
    {
        if (! $loan->return_date) {
            return 'with no due date';
        }

        return 'due '.$loan->return_date->format($loan->timed ? 'M j, Y, g:i A' : 'M j, Y');
    }

    public static function loanStatus(BorrowTransaction $loan): string
    {
        $status = $loan->derivedStatus();

        return $status === 'Out' ? 'Borrowed' : $status;
    }

    public static function changes(array $old, array $new): array
    {
        $changes = [];
        $plain = fn ($value) => is_string($value) && preg_match('/^-?\d+$/', $value) ? (int) $value : $value;

        foreach ($new as $field => $value) {
            $before = $old[$field] ?? null;
            if ((string) $before !== (string) $value) {
                $changes[$field] = ['old' => $plain($before), 'new' => $plain($value)];
            }
        }

        return $changes;
    }

    public static function changeWords(array $changes, array $labels = []): string
    {
        $short = fn ($value) => mb_strlen((string) $value) <= 40 && ! str_contains((string) $value, "\n");

        return collect($changes)->map(function (array $change, string $field) use ($labels, $short) {
            $label = $labels[$field] ?? str_replace('_', ' ', $field);

            return $short($change['old']) && $short($change['new'])
                ? $label.' '.($change['old'] ?? '—').' → '.($change['new'] ?? '—')
                : $label.' changed';
        })->join(', ');
    }

    private static function fit(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw self::appendOnly('changed'));
        static::deleting(fn () => throw self::appendOnly('deleted'));
    }

    public static function appendOnly(string $what): LogicException
    {
        return new LogicException("Activity log entries are append-only and cannot be {$what}.");
    }

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

    public function typeLabel(): string
    {
        return self::TYPES[$this->type]['label'] ?? Str::headline((string) $this->type);
    }

    public function group(): string
    {
        return self::TYPES[$this->type]['group'] ?? 'Other';
    }

    public function groupLabel(): string
    {
        return self::groupName($this->group());
    }

    public static function groupName(string $group): string
    {
        return self::GROUP_LABELS[$group] ?? $group;
    }

    public static function typesInGroup(string $group): array
    {
        return array_keys(array_filter(
            self::TYPES,
            fn (array $type) => strcasecmp($type['group'], $group) === 0
        ));
    }

    public function actorLabel(): string
    {
        return $this->actor_name ?? 'System';
    }

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

    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }

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
