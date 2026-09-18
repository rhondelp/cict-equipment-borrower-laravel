<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Equipment extends Model
{
    //
    protected $table = 'equipment';

    protected $fillable = ['equipment_name', 'description', 'quantity', 'available_quantity', 'status'];

    /**
     * Return $quantity units to the shelf and persist the recomputed status.
     *
     * Callers are responsible for the surrounding DB transaction and for having
     * locked this row (lockForUpdate) before calling.
     */
    public function releaseStock(int $quantity): void
    {
        $this->available_quantity += $quantity;
        $this->status = $this->available_quantity > 0 ? 'Available' : 'Unavailable';
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
        $this->status = $this->available_quantity > 0 ? 'Available' : 'Unavailable';
        $this->save();
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
