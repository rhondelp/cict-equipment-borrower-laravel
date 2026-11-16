<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_type',
        'name',
        'email',
        'password',
        'contact_number',
        'course',
        'year_level',
        'deactivated_at',
        'suspended_at',
        'suspension_reason',
        'suspended_by',
        'role_overridden_at',
        'role_override_reason',
        'role_overridden_by',
        'instructor_requested_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'role_overridden_at' => 'datetime',
            'instructor_requested_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const SCHOOL_DOMAIN = 'nmsc.edu.ph';

    public const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    public function courseAndYear(): ?string
    {
        $parts = array_filter([$this->course, $this->year_level]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public static function emailDomain(?string $email): ?string
    {
        $parts = explode('@', trim((string) $email));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return strtolower($parts[1]);
    }

    public static function isSchoolEmail(?string $email): bool
    {
        return self::emailDomain($email) === self::SCHOOL_DOMAIN;
    }

    public function hasPendingInstructorRequest(): bool
    {
        return $this->instructor_requested_at !== null;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function canBorrow(): bool
    {
        return ! $this->isDeactivated() && ! $this->isSuspended();
    }

    public function suspensionLine(): string
    {
        if (! $this->isSuspended()) {
            return '';
        }

        $since = $this->suspended_at?->format('M j');

        return 'Suspended'.($since ? ' since '.$since : '')
            .($this->suspension_reason ? ' · '.$this->suspension_reason : '');
    }

    public function roleIsOverridden(): bool
    {
        return $this->role_overridden_at !== null;
    }

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

    public function unitsOut(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->sum('quantity');
    }

    public function historyCount(): int
    {
        return $this->borrowTransactions()->count() + $this->itemRequests()->count();
    }

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
