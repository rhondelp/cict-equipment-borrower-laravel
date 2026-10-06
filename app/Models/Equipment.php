<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Equipment extends Model
{
    //
    protected $table = 'equipment';

    protected $fillable = ['equipment_name', 'description', 'category', 'loan_type', 'quantity', 'available_quantity', 'status', 'retired_at'];

    /**
     * How an item leaves the room. Returnable is lent and brought back by a due
     * date; time-limited is lent for hours and due back at a time of day;
     * non-returnable is handed over for good and recorded as Issued.
     */
    public const LOAN_RETURNABLE = 'returnable';

    public const LOAN_TIME_LIMITED = 'time_limited';

    public const LOAN_NON_RETURNABLE = 'non_returnable';

    /** Stored key => the label a screen shows. */
    public const LOAN_TYPES = [
        self::LOAN_RETURNABLE => 'Returnable',
        self::LOAN_TIME_LIMITED => 'Time-Limited',
        self::LOAN_NON_RETURNABLE => 'Non-Returnable',
    ];

    /** Mirrors the column default, so an unsaved model answers the same way. */
    protected $attributes = ['loan_type' => self::LOAN_RETURNABLE];

    protected function casts(): array
    {
        return ['retired_at' => 'datetime'];
    }

    /**
     * Return $quantity units to the shelf and persist the recomputed status.
     *
     * Callers are responsible for the surrounding DB transaction and for having
     * locked this row (lockForUpdate) before calling.
     */
    public function releaseStock(int $quantity, ?BorrowTransaction $loan = null): void
    {
        $before = $this->status;

        $this->available_quantity += $quantity;
        $this->status = $this->lendableStatus();
        $this->save();

        $this->recordStatusFlip($before, $quantity, $loan);
    }

    /**
     * Take $quantity units off the shelf and persist the recomputed status.
     *
     * Throws when stock is short. $message overrides the default wording for
     * the call sites that report the equipment name and the exact shortfall.
     *
     * Callers are responsible for the surrounding DB transaction and for having
     * locked this row (lockForUpdate) before calling.
     *
     * @throws ValidationException
     */
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

    /**
     * Log the moment an item runs out or comes back, and only that moment.
     *
     * Kept here, in the two stock helpers, rather than in the controllers:
     * every path that moves stock goes through one of them, so none can move
     * an item to Unavailable without the log saying so, and a movement that
     * leaves the status as it was writes nothing. The caller's transaction
     * holds the row lock, so the entry commits or rolls back with the stock.
     */
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

    /**
     * The `status` column is derived, never typed: an item is Available when it
     * has units on the shelf and has not been retired. Kept in one place so the
     * two stock helpers and the controller cannot drift apart.
     */
    public function lendableStatus(): string
    {
        return (! $this->isRetired() && $this->available_quantity > 0) ? 'Available' : 'Unavailable';
    }

    /**
     * Tidy a typed category and reuse the spelling already on file.
     *
     * Whitespace is trimmed and collapsed, an empty value means "none", and a
     * match against an existing category ignores case — so typing "cables"
     * when "Cables" exists files the item under "Cables" rather than starting
     * a second group. A genuinely new name is kept exactly as typed, because
     * "HDMI" or "USB-C adapters" would be mangled by any automatic casing.
     */
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

    /** Every distinct category on file, A–Z — the suggestions the form offers. */
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

    /**
     * What the borrower is told before asking for this item: "Return by a
     * date", "Return within 1 hour" (from config('office.time_limited_minutes')),
     * or "Given to you — no return needed".
     */
    public function borrowerReturnNote(): string
    {
        return match ($this->loan_type) {
            self::LOAN_TIME_LIMITED => 'Return within '.static::timeLimitLabel(),
            self::LOAN_NON_RETURNABLE => 'Given to you — no return needed',
            default => 'Return by a date',
        };
    }

    /** config('office.time_limited_minutes') in words: "1 hour", "90 minutes", "2 hours". */
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

    /** Items that can still be lent out. Retired rows keep their history. */
    public function scopeLendable(Builder $query): Builder
    {
        return $query->whereNull('retired_at');
    }

    /**
     * Units currently with borrowers, counted from the loans themselves rather
     * than inferred from available_quantity. This is the figure the delete
     * confirm quotes and the figure a total cannot be pushed below.
     */
    public function unitsOut(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->sum('quantity');
    }

    /**
     * Units handed over for good on non-voided Issued transactions. They are
     * not "out" — nothing is coming back — but they have left the shelf, so
     * they come off the available figure for as long as the hand-over stands.
     * Voiding the transaction puts them back.
     */
    public function unitsIssued(): int
    {
        return (int) $this->borrowTransactions()
            ->whereNull('voided_at')
            ->where('status', 'Issued')
            ->sum('quantity');
    }

    /**
     * What available_quantity should read, worked out from the transactions
     * rather than trusted: total owned, less units on loan, less units issued.
     * EquipmentController::update writes this on every edit, which also
     * repairs drift.
     */
    public function derivedAvailableQuantity(): int
    {
        return (int) $this->quantity - $this->unitsOut() - $this->unitsIssued();
    }

    /** Loans and requests that would disappear with a hard delete. */
    public function historyCount(): int
    {
        return $this->borrowTransactions()->count() + $this->itemRequests()->count();
    }

    /**
     * The two figures above, preferring the aggregates the index queries load
     * with withSum/withCount. A view can ask every row without firing a query
     * per row; anything holding a bare model still gets the right answer.
     */
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

    /**
     * How this item reads in a list: one label plus the tone it carries.
     * Derived from stock on every render, so it cannot contradict the loans.
     *
     * @return array{key: string, label: string, tone: string}
     */
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

    public function borrowTransactions()
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function itemRequests()
    {
        return $this->hasMany(ItemRequest::class);
    }
}
