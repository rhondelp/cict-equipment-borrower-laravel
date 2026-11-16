<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReturnLog extends Model
{
    protected $fillable = [
        'borrow_transaction_id', 'return_date', 'condition', 'remarks', 'user_id',
        'resolution', 'resolved_at', 'resolved_by',
    ];

    public const CONDITIONS = ['Good', 'Minor damage', 'Damaged', 'Lost'];

    public const LEGACY_CONDITIONS = ['Missing parts'];

    protected function casts(): array
    {
        return ['return_date' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function daysLate(): int
    {
        $due = $this->borrowTransaction?->return_date;

        if (! $due || ! $this->return_date || $this->return_date->startOfDay()->lte($due->startOfDay())) {
            return 0;
        }

        return (int) $due->startOfDay()->diffInDays($this->return_date->startOfDay());
    }

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
            'Lost', 'Damaged' => 'danger',
            default => 'warning',
        };
    }

    public function isIncident(): bool
    {
        return $this->condition !== 'Good';
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function needsFollowUp(): bool
    {
        return $this->isIncident() && ! $this->isResolved();
    }

    public function scopeNeedsFollowUp(Builder $query): Builder
    {
        return $query->where('condition', '!=', 'Good')->whereNull('resolved_at');
    }

    public static function defects(): Builder
    {
        return static::query()
            ->needsFollowUp()
            ->with(['borrowTransaction.equipment', 'borrowTransaction.user'])
            ->orderBy('return_date')
            ->orderBy('id');
    }

    public function resolutionLine(): string
    {
        if (! $this->isResolved()) {
            return 'No resolution recorded';
        }

        $when = $this->resolved_at?->format('M j');
        $who = $this->resolver?->name;

        return trim('Resolved'.($when ? ' '.$when : '').($who ? ' by '.$who : ''));
    }

    public function notes()
    {
        return $this->hasMany(ReturnLogNote::class)->oldest();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function borrower()
    {
        return $this->hasOneThrough(
            User::class,
            BorrowTransaction::class,
            'id',
            'id',
            'borrow_transaction_id',
            'user_id'
        );
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
