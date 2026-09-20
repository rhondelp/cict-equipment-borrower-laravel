<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLog extends Model
{
    protected $fillable = ['borrow_transaction_id', 'return_date', 'condition', 'remarks', 'user_id'];

    protected function casts(): array
    {
        return ['return_date' => 'datetime'];
    }

    /** Whole days between the loan's due date and the day it actually came back. */
    public function daysLate(): int
    {
        $due = $this->borrowTransaction?->return_date;

        if (! $due || ! $this->return_date || $this->return_date->startOfDay()->lte($due->startOfDay())) {
            return 0;
        }

        return (int) $due->startOfDay()->diffInDays($this->return_date->startOfDay());
    }

    /** "Sep 18 · 4 days late" — the whole date cell in one string. */
    public function timingLine(): string
    {
        $late = $this->daysLate();
        $day = $this->return_date?->format('M j') ?? '—';

        return $late > 0
            ? $day.' · '.$late.' '.str('day')->plural($late).' late'
            : $day.' · on time';
    }

    public function conditionTone(): string
    {
        return match ($this->condition) {
            'Good' => 'success',
            'Damaged' => 'danger',
            default => 'warning',
        };
    }

    // Borrow transaction itself
    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    // The borrower (via BorrowTransaction → User)
    public function borrower()
    {
        return $this->hasOneThrough(
            User::class,            // final model
            BorrowTransaction::class, // intermediate model
            'id',                   // FK on BorrowTransaction
            'id',                   // FK on User
            'borrow_transaction_id',// FK on ReturnLog
            'user_id'               // FK on BorrowTransaction
        );
    }

    // The receiver (staff who processed return → stored in return_logs.user_id)
    public function receiver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Equipment (via BorrowTransaction)
    public function equipment()
    {
        return $this->hasOneThrough(
            Equipment::class,
            BorrowTransaction::class,
            'id',
            'id',
            'borrow_transaction_id',
            'equipment_id'
        );
    }
}
