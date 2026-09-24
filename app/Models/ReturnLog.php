<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLog extends Model
{
    protected $fillable = [
        'borrow_transaction_id', 'return_date', 'condition', 'remarks', 'user_id',
        'resolution', 'resolved_at', 'resolved_by',
    ];

    /**
     * The conditions a return can be logged in, worst last.
     *
     * `Missing parts` is not offered any more — it is kept accepted because
     * rows written before this exist with that value, and rewriting history to
     * tidy up a vocabulary is exactly what an audit log must not do.
     */
    public const CONDITIONS = ['Good', 'Minor damage', 'Damaged', 'Lost'];

    public const LEGACY_CONDITIONS = ['Missing parts'];

    protected function casts(): array
    {
        return ['return_date' => 'datetime', 'resolved_at' => 'datetime'];
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

    /** Colour carries the severity, so the scale has to run all the way down. */
    public function conditionTone(): string
    {
        return match ($this->condition) {
            'Good' => 'success',
            'Lost', 'Damaged' => 'danger',
            default => 'warning',
        };
    }

    /** Anything other than a clean return is an incident. */
    public function isIncident(): bool
    {
        return $this->condition !== 'Good';
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * The rows the screen leads with: something came back damaged or lost and
     * nobody has recorded what was done about it. Everything else is archive.
     */
    public function needsFollowUp(): bool
    {
        return $this->isIncident() && ! $this->isResolved();
    }

    /** "Resolved Sep 20 by Quincy Jane O." */
    public function resolutionLine(): string
    {
        if (! $this->isResolved()) {
            return 'No resolution recorded';
        }

        $when = $this->resolved_at?->format('M j');
        $who = $this->resolver?->name;

        return trim('Resolved'.($when ? ' '.$when : '').($who ? ' by '.$who : ''));
    }

    /**
     * Corrections, appended. The log row itself never changes — there is no
     * update and no delete path to one — so a correction is a new note with
     * its own author and time, and the original reading survives beside it.
     */
    public function notes()
    {
        return $this->hasMany(ReturnLogNote::class)->oldest();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
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
