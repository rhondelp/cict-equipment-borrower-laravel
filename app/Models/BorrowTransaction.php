<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BorrowTransaction extends Model
{
    protected $fillable = ['user_id', 'equipment_id', 'borrow_date', 'return_date', 'quantity', 'purpose', 'status', 'remarks', 'class_schedule_id', 'voided_at', 'void_reason', 'timed'];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'datetime',
            'return_date' => 'datetime',
            'timed' => 'boolean',
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

    public function reminders()
    {
        return $this->hasMany(Notification::class)->latest('send_date');
    }

    public function returnLog()
    {
        return $this->hasOne(ReturnLog::class);
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isReturned(): bool
    {
        return $this->status === 'Returned';
    }

    public function isOut(): bool
    {
        return ! $this->isVoided() && in_array($this->status, ['Borrowed', 'Overdue'], true);
    }

    public function isIssued(): bool
    {
        return ! $this->isVoided() && $this->status === 'Issued';
    }

    public function dueAt(): ?CarbonInterface
    {
        if ($this->return_date === null) {
            return null;
        }

        return $this->timed ? $this->return_date->copy() : $this->return_date->copy()->endOfDay();
    }

    public function scopeOut(Builder $query): Builder
    {
        return $query->whereNull('voided_at')->whereIn('status', ['Borrowed', 'Overdue']);
    }

    public function scopeOverdue(Builder $query, ?CarbonInterface $now = null): Builder
    {
        [$moment, $today] = static::overdueBounds($now);

        return $query->out()->where(fn (Builder $due) => $due
            ->where(fn (Builder $timed) => $timed->where('timed', true)->where('return_date', '<', $moment))
            ->orWhere(fn (Builder $dated) => $dated->where('timed', false)->where('return_date', '<', $today)));
    }

    public static function overdueCaseSql(?CarbonInterface $now = null): array
    {
        [$moment, $today] = static::overdueBounds($now);

        return [
            'CASE WHEN (timed = 1 AND return_date < ?) OR (timed = 0 AND return_date < ?) THEN 1 ELSE 0 END',
            [$moment, $today],
        ];
    }

    private static function overdueBounds(?CarbonInterface $now): array
    {
        $now = $now ? Carbon::instance($now) : now();

        return [$now->format('Y-m-d H:i:s'), $now->copy()->startOfDay()->format('Y-m-d H:i:s')];
    }

    public function isOverdue(): bool
    {
        $due = $this->dueAt();

        return $this->isOut() && $due !== null && $due->lt(now());
    }

    public function derivedStatus(): string
    {
        if ($this->isVoided()) {
            return 'Void';
        }
        if ($this->isIssued()) {
            return 'Issued';
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
            'Void', 'Issued' => 'neutral',
            default => 'primary',
        };
    }

    public function daysLate(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        if ($this->timed) {
            return (int) floor($this->dueAt()->diffInDays(now()));
        }

        return (int) $this->return_date->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function daysUntilDue(): ?int
    {
        if ($this->return_date === null || $this->isIssued()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->return_date->copy()->startOfDay(), false);
    }

    public function bookingKey(): string
    {
        return $this->bookingMoment($this->borrow_date).'|'.$this->bookingMoment($this->return_date).'|'.trim((string) $this->purpose);
    }

    private function bookingMoment(?CarbonInterface $date): ?string
    {
        return $this->timed ? $date?->format('Y-m-d H:i') : $date?->toDateString();
    }

    public function bookingLoans()
    {
        return static::with('equipment')
            ->where('user_id', $this->user_id)
            ->whereNull('voided_at')
            ->whereDate('borrow_date', $this->borrow_date)
            ->when(
                $this->return_date,
                fn ($query) => $query->whereDate('return_date', $this->return_date),
                fn ($query) => $query->whereNull('return_date')
            )
            ->get()
            ->filter(fn ($loan) => $loan->bookingKey() === $this->bookingKey())
            ->sortBy('id')
            ->values();
    }

    public function dateRangeLabel(): string
    {
        if ($this->status === 'Issued') {
            return 'Issued '.$this->formatDay($this->borrow_date);
        }

        if ($this->timed) {
            $from = $this->formatMoment($this->borrow_date);
            $to = match (true) {
                $this->return_date === null => 'open',
                $this->borrow_date?->isSameDay($this->return_date) === true => $this->return_date->format('g:i A'),
                default => $this->formatMoment($this->return_date),
            };

            return $from.' → '.$to;
        }

        $from = $this->formatDay($this->borrow_date);
        $to = $this->return_date ? $this->formatDay($this->return_date) : 'open';

        return $from.' → '.$to;
    }

    public function timingLabel(): string
    {
        if ($this->isVoided()) {
            return 'voided';
        }

        if ($this->isIssued()) {
            return 'issued';
        }

        if ($this->timed) {
            return $this->timedTimingLabel();
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

    public function dateLine(): string
    {
        if ($this->isIssued()) {
            return $this->dateRangeLabel();
        }

        return $this->dateRangeLabel().' · '.$this->timingLabel();
    }

    private function timedTimingLabel(): string
    {
        $due = $this->dueAt();

        if ($this->isReturned()) {
            $log = $this->relationLoaded('returnLog') ? $this->returnLog : $this->returnLog()->first();
            $returnedAt = $log?->created_at;

            if ($returnedAt && $due && $returnedAt->gt($due)) {
                return $this->lateLabel($due, $returnedAt);
            }

            return 'returned on time';
        }

        if ($due === null) {
            return 'no due date';
        }

        $now = now();

        if ($due->lt($now)) {
            return $this->lateLabel($due, $now);
        }

        $minutes = (int) ceil($now->diffInMinutes($due));

        return match (true) {
            $minutes === 0 => 'due now',
            $minutes < 60 => 'due in '.$minutes.' min',
            $due->isSameDay($now) => 'due at '.$due->format('g:i A'),
            $due->isSameDay($now->copy()->addDay()) => 'due tomorrow, '.$due->format('g:i A'),
            default => 'due '.$this->formatMoment($due),
        };
    }

    private function lateLabel(CarbonInterface $due, CarbonInterface $at): string
    {
        $minutes = (int) ceil($due->diffInMinutes($at));

        if ($minutes < 60) {
            return $minutes.' min late';
        }

        if ($minutes < 24 * 60) {
            return intdiv($minutes, 60).' hr late';
        }

        $days = intdiv($minutes, 24 * 60);

        return $days.' '.str('day')->plural($days).' late';
    }

    private function formatDay(?CarbonInterface $date): string
    {
        return $date ? $date->format('M j') : '—';
    }

    private function formatMoment(?CarbonInterface $date): string
    {
        return $date ? $date->format('M j, g:i A') : '—';
    }
}
