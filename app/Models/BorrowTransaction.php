<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class BorrowTransaction extends Model
{
    //
    protected $fillable = ['user_id', 'equipment_id', 'borrow_date', 'return_date', 'quantity', 'purpose', 'status', 'remarks', 'class_schedule_id', 'voided_at', 'void_reason'];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'return_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Reminders sent about this loan, newest first. Written by
     * BorrowTransactionController::sendManualEmail so the loans screen can tell
     * a first nudge from a fourth.
     */
    public function reminders()
    {
        return $this->hasMany(Notification::class)->latest('send_date');
    }

    public function returnLog()
    {
        return $this->hasOne(ReturnLog::class);
    }

    /* ---------------------------------------------------------------------
     | Derived state
     |
     | The stored `status` enum is what the stock arithmetic keys off, and the
     | nightly job still writes Overdue so the reminder mails can find their
     | targets. What a screen *shows*, though, is worked out here from the due
     | date every time it renders — so a loan reads as overdue the morning it
     | becomes overdue, not the morning after the job next runs.
     --------------------------------------------------------------------- */

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isReturned(): bool
    {
        return $this->status === 'Returned';
    }

    /** Units are off the shelf: stock is deducted and the loan is still open. */
    public function isOut(): bool
    {
        return ! $this->isVoided() && in_array($this->status, ['Borrowed', 'Overdue'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOut()
            && $this->return_date !== null
            && $this->return_date->startOfDay()->lt(now()->startOfDay());
    }

    /** 'Void' | 'Returned' | 'Overdue' | 'Out' */
    public function derivedStatus(): string
    {
        if ($this->isVoided()) {
            return 'Void';
        }
        if ($this->isReturned()) {
            return 'Returned';
        }

        return $this->isOverdue() ? 'Overdue' : 'Out';
    }

    public function statusTone(): string
    {
        return match ($this->derivedStatus()) {
            'Overdue' => 'danger',
            'Returned' => 'success',
            'Void' => 'neutral',
            default => 'primary',
        };
    }

    /** Whole days past the due date; 0 when not overdue. */
    public function daysLate(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->return_date->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** Whole days until the due date; negative once it has passed. */
    public function daysUntilDue(): ?int
    {
        if ($this->return_date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->return_date->startOfDay(), false);
    }

    /** "Sep 10 → Sep 17" — never a raw ISO date. */
    public function dateRangeLabel(): string
    {
        $from = $this->formatDay($this->borrow_date);
        $to = $this->return_date ? $this->formatDay($this->return_date) : 'open';

        return $from.' → '.$to;
    }

    /**
     * The half of the date line that carries the urgency:
     * "3 days late", "due tomorrow", "returned on time".
     */
    public function timingLabel(): string
    {
        if ($this->isVoided()) {
            return 'voided';
        }

        if ($this->isReturned()) {
            $log = $this->relationLoaded('returnLog') ? $this->returnLog : $this->returnLog()->first();
            $returnedOn = $log?->return_date ? \Carbon\Carbon::parse($log->return_date) : null;

            if ($returnedOn && $this->return_date && $returnedOn->startOfDay()->gt($this->return_date->startOfDay())) {
                $late = (int) $this->return_date->startOfDay()->diffInDays($returnedOn->startOfDay());

                return $late.' '.str('day')->plural($late).' late';
            }

            return 'returned on time';
        }

        $days = $this->daysUntilDue();
        if ($days === null) {
            return 'no due date';
        }

        if ($days < 0) {
            $late = abs($days);

            return $late.' '.str('day')->plural($late).' late';
        }

        return match ($days) {
            0 => 'due today',
            1 => 'due tomorrow',
            default => 'due in '.$days.' days',
        };
    }

    /** The whole line: "Sep 10 → Sep 17 · 3 days late". */
    public function dateLine(): string
    {
        return $this->dateRangeLabel().' · '.$this->timingLabel();
    }

    private function formatDay(?CarbonInterface $date): string
    {
        return $date ? $date->format('M j') : '—';
    }
}
