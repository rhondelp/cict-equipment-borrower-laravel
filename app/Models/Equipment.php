<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Equipment extends Model
{
    //
    protected $table = 'equipment';

    protected $fillable = ['equipment_name', 'description', 'quantity', 'available_quantity', 'status', 'retired_at'];

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
    public function releaseStock(int $quantity): void
    {
        $this->available_quantity += $quantity;
        $this->status = $this->lendableStatus();
        $this->save();
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
    public function reserveStock(int $quantity, ?string $message = null): void
    {
        if ($this->available_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => $message ?? 'Not enough equipment available.',
            ]);
        }

        $this->available_quantity -= $quantity;
        $this->status = $this->lendableStatus();
        $this->save();
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
