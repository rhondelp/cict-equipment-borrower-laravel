<?php

namespace App\Models;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'user_type',
        'name',
        'email',
        'password',
        'contact_number',
        'deactivated_at',
        'suspended_at',
        'suspension_reason',
        'suspended_by',
        'role_overridden_at',
        'role_override_reason',
        'role_overridden_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'role_overridden_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The two school domains a borrower account can come from. Role is read
     * from the address rather than chosen on the form: picking your own role is
     * a privilege boundary, and a select is a request the client controls.
     */
    public const STUDENT_DOMAIN = 'student.nmsc.edu.ph';

    public const STAFF_DOMAIN = 'nmsc.edu.ph';

    /**
     * The domain half of an address, lowercased. Null when there is not exactly
     * one `@` with something either side of it.
     */
    public static function emailDomain(?string $email): ?string
    {
        $parts = explode('@', trim((string) $email));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return strtolower($parts[1]);
    }

    /**
     * The role a school address implies — `Student` for the student subdomain,
     * `Instructor` for the staff domain, null for anything else.
     *
     * Matched on the whole domain, never on a suffix: `endsWith('nmsc.edu.ph')`
     * would hand an Instructor account to anyone who registers
     * `not-nmsc.edu.ph`. `Admin` is not reachable from here at all, which is
     * the point — it stays a deliberate database action.
     */
    public static function roleForEmail(?string $email): ?string
    {
        return match (self::emailDomain($email)) {
            self::STUDENT_DOMAIN => 'Student',
            self::STAFF_DOMAIN => 'Instructor',
            default => null,
        };
    }

    /** Whether an address is one this system issues accounts for. */
    public static function isSchoolEmail(?string $email): bool
    {
        return self::roleForEmail($email) !== null;
    }

    /**
     * Deactivating is the non-destructive half of the Remove dialog: the
     * account stops working, every loan and log that names this person stays
     * exactly where it is. AuthenticateUser::login refuses a deactivated
     * account, so the flag is not cosmetic.
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Suspension is not deactivation.
     *
     * A deactivated account cannot sign in at all. A suspended one signs in,
     * sees its history and its open loans, and cannot borrow — which is the
     * sanction the terms of service already describe. Enforced in
     * ItemRequestController::store, not only by hiding a button.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function canBorrow(): bool
    {
        return ! $this->isDeactivated() && ! $this->isSuspended();
    }

    /** "Suspended since Sep 14 · two items never came back" */
    public function suspensionLine(): string
    {
        if (! $this->isSuspended()) {
            return '';
        }

        $since = $this->suspended_at?->format('M j');

        return 'Suspended'.($since ? ' since '.$since : '')
            .($this->suspension_reason ? ' · '.$this->suspension_reason : '');
    }

    /**
     * Whether this account's role matches what its email domain implies.
     *
     * Role is derived from the domain; an admin may override it, but the
     * override is a recorded decision rather than an inline edit, and the
     * screen shows both the derived answer and the override beside it.
     */
    public function roleMatchesDomain(): bool
    {
        $derived = self::roleForEmail($this->email);

        // An address outside both school domains implies nothing, so there is
        // nothing for the stored role to contradict.
        return $derived === null || $derived === $this->user_type;
    }

    public function roleIsOverridden(): bool
    {
        return $this->role_overridden_at !== null;
    }

    /** "Set to Instructor by Quincy Jane O. on Sep 20 — teaches lab sections" */
    public function roleOverrideLine(): string
    {
        if (! $this->roleIsOverridden()) {
            return '';
        }

        $who = $this->roleOverrider?->name;
        $when = $this->role_overridden_at?->format('M j');

        return 'Set to '.$this->user_type
            .($who ? ' by '.$who : '')
            .($when ? ' on '.$when : '')
            .($this->role_override_reason ? ' — '.$this->role_override_reason : '');
    }

    /** Requests this person is waiting on a decision for. */
    public function pendingRequests(): int
    {
        return isset($this->attributes['pending_requests_count'])
            ? (int) $this->attributes['pending_requests_count']
            : $this->itemRequests()->where('status', 'Pending')->count();
    }

    public function suspender()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function roleOverrider()
    {
        return $this->belongsTo(User::class, 'role_overridden_by');
    }

    /** Units this person is holding right now. */
    public function unitsOut(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->sum('quantity');
    }

    /** Loans and requests that a hard delete would cascade away. */
    public function historyCount(): int
    {
        return $this->borrowTransactions()->count() + $this->itemRequests()->count();
    }

    /** Same two figures, preferring the aggregates the users index loads. */
    public function outNow(): int
    {
        return isset($this->attributes['units_out']) ? (int) $this->attributes['units_out'] : $this->unitsOut();
    }

    public function referencesCount(): int
    {
        if (isset($this->attributes['borrow_transactions_count'], $this->attributes['item_requests_count'])) {
            return (int) $this->attributes['borrow_transactions_count'] + (int) $this->attributes['item_requests_count'];
        }

        return $this->historyCount();
    }

    public function borrowTransactions()
    {
        return $this->hasMany(BorrowTransaction::class);
    }
    public function itemRequests()
    {
        return $this->hasMany(ItemRequest::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
