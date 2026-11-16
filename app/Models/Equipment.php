<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = ['equipment_name', 'description', 'category', 'loan_type', 'quantity', 'unit', 'available_quantity', 'status', 'retired_at'];

    public const LOAN_RETURNABLE = 'returnable';

    public const LOAN_TIME_LIMITED = 'time_limited';

    public const LOAN_NON_RETURNABLE = 'non_returnable';

    public const LOAN_TYPES = [
        self::LOAN_RETURNABLE => 'Returnable',
        self::LOAN_TIME_LIMITED => 'Time-Limited',
        self::LOAN_NON_RETURNABLE => 'Non-Returnable',
    ];

    public const DEFAULT_UNIT = 'pc';

    protected $attributes = ['loan_type' => self::LOAN_RETURNABLE, 'unit' => self::DEFAULT_UNIT];

    protected function casts(): array
    {
        return ['retired_at' => 'datetime'];
    }

    public function releaseStock(int $quantity, ?BorrowTransaction $loan = null): void
    {
        $before = $this->status;

        $this->available_quantity += $quantity;
        $this->status = $this->lendableStatus();
        $this->save();

        $this->recordStatusFlip($before, $quantity, $loan);
    }

    public function reserveStock(int $quantity, ?string $message = null, ?BorrowTransaction $loan = null): void
    {
        if ($this->available_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => $message ?? 'Not enough equipment available.',
            ]);
        }

        $before = $this->status;

        $this->available_quantity -= $quantity;
        $this->status = $this->lendableStatus();
        $this->save();

        $this->recordStatusFlip($before, $quantity, $loan);
    }

    private function recordStatusFlip(?string $before, int $quantity, ?BorrowTransaction $loan): void
    {
        if ($before === $this->status) {
            return;
        }

        ActivityLog::record('equipment_status_changed', [
            'equipment' => $this,
            'loan' => $loan,
            'quantity' => $quantity,
            'status_from' => $before,
            'status_to' => $this->status,
            'details' => $this->status === 'Available'
                ? $this->equipment_name.' is back on the shelf ('.$this->available_quantity.' available).'
                : $this->equipment_name.' is no longer lendable — '.($this->isRetired() ? 'it is retired.' : 'none left on the shelf.'),
            'meta' => ['available_quantity' => $this->available_quantity, 'quantity' => $this->quantity],
        ]);
    }

    public function lendableStatus(): string
    {
        return (! $this->isRetired() && $this->available_quantity > 0) ? 'Available' : 'Unavailable';
    }

    public static function canonicalCategory(?string $category): ?string
    {
        $category = trim(preg_replace('/\s+/u', ' ', (string) $category));

        if ($category === '') {
            return null;
        }

        return static::categoriesInUse()
            ->first(fn ($existing) => mb_strtolower($existing) === mb_strtolower($category))
            ?? $category;
    }

    public static function categoriesInUse()
    {
        return static::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function isReturnable(): bool
    {
        return $this->loan_type === self::LOAN_RETURNABLE;
    }

    public function isTimeLimited(): bool
    {
        return $this->loan_type === self::LOAN_TIME_LIMITED;
    }

    public function isNonReturnable(): bool
    {
        return $this->loan_type === self::LOAN_NON_RETURNABLE;
    }

    public function loanTypeLabel(): string
    {
        return self::LOAN_TYPES[$this->loan_type] ?? self::LOAN_TYPES[self::LOAN_RETURNABLE];
    }

    public function borrowerReturnNote(): string
    {
        return match ($this->loan_type) {
            self::LOAN_TIME_LIMITED => 'Return within '.static::timeLimitLabel(),
            self::LOAN_NON_RETURNABLE => 'Given to you — no return needed',
            default => 'Return by a date',
        };
    }

    public static function timeLimitLabel(): string
    {
        $minutes = max(1, (int) config('office.time_limited_minutes', 60));

        if ($minutes % 60 === 0) {
            $hours = intdiv($minutes, 60);

            return $hours.' '.str('hour')->plural($hours);
        }

        return $minutes.' '.str('minute')->plural($minutes);
    }

    public function isRetired(): bool
    {
        return $this->retired_at !== null;
    }

    public function scopeLendable(Builder $query): Builder
    {
        return $query->whereNull('retired_at');
    }

    public function unitsOut(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->sum('quantity');
    }

    public function unitsIssued(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->where('status', 'Issued')
            ->sum('quantity');
    }

    public function derivedAvailableQuantity(): int
    {
        return (int) $this->quantity - $this->unitsOut() - $this->unitsIssued();
    }

    public function historyCount(): int
    {
        return $this->borrowTransactions()->count() + $this->itemRequests()->count();
    }

    public function outNow(): int
    {
        return isset($this->attributes['units_out']) ? (int) $this->attributes['units_out'] : $this->unitsOut();
    }

    public function issuedNow(): int
    {
        return isset($this->attributes['units_issued']) ? (int) $this->attributes['units_issued'] : $this->unitsIssued();
    }

    public function referencesCount(): int
    {
        if (isset($this->attributes['borrow_transactions_count'], $this->attributes['item_requests_count'])) {
            return (int) $this->attributes['borrow_transactions_count'] + (int) $this->attributes['item_requests_count'];
        }

        return $this->historyCount();
    }

    public function availabilityState(): array
    {
        if ($this->isRetired()) {
            return ['key' => 'retired', 'label' => 'Retired', 'tone' => 'neutral'];
        }

        $available = (int) $this->available_quantity;
        $total = max(1, (int) $this->quantity);
        $share = $available / $total;

        if ($available <= 0) {
            return ['key' => 'out', 'label' => 'Fully out', 'tone' => 'danger'];
        }
        if ($share <= 0.3) {
            return ['key' => 'low', 'label' => 'Running low', 'tone' => 'warning'];
        }
        if ($available < (int) $this->quantity) {
            return ['key' => 'partial', 'label' => 'Partly out', 'tone' => 'primary'];
        }

        return ['key' => 'all-in', 'label' => 'All in', 'tone' => 'success'];
    }

    public function isServiceable(): bool
    {
        if (isset($this->attributes['has_open_defects'])) {
            return ! $this->attributes['has_open_defects'];
        }

        return ! $this->returnLogs()->needsFollowUp()->exists();
    }

    public function scopeServiceable(Builder $query): Builder
    {
        return $query->whereDoesntHave('returnLogs', fn ($logs) => $logs->needsFollowUp());
    }

    public function scopeNonServiceable(Builder $query): Builder
    {
        return $query->whereHas('returnLogs', fn ($logs) => $logs->needsFollowUp());
    }

    public function defects()
    {
        return ReturnLog::defects()->whereHas('borrowTransaction', fn ($loan) => $loan->where('equipment_id', $this->id));
    }

    public function borrowTransactions()
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function returnLogs()
    {
        return $this->hasManyThrough(ReturnLog::class, BorrowTransaction::class);
    }

    public function itemRequests()
    {
        return $this->hasMany(ItemRequest::class);
    }
}
