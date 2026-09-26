<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The equipment room's opening hours, read from config('office.hours').
 *
 * Every page that states the hours goes through here, so a change to the
 * config changes every page at once — the landing page's status pill, facts
 * band and visit panel, the sign-in panel, the register and forgot-password
 * copy, and the borrower dashboard.
 */
final class OfficeHours
{
    private const DAY_NAMES = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    /**
     * @param  list<int>  $days  ISO-8601 weekday numbers, 1 = Monday
     */
    public function __construct(
        private readonly array $days,
        private readonly string $open,
        private readonly string $close,
    ) {}

    public static function fromConfig(): self
    {
        $hours = config('office.hours');

        $days = collect($hours['days'] ?? [])->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 1 && $day <= 7)
            ->unique()->sort()->values()->all();

        return new self($days, (string) ($hours['open'] ?? '08:00'), (string) ($hours['close'] ?? '17:00'));
    }

    /** "8:00 AM – 5:00 PM" */
    public function timeRange(): string
    {
        return $this->format($this->open).' – '.$this->format($this->close);
    }

    /** "5:00 PM" */
    public function closingTime(): string
    {
        return $this->format($this->close);
    }

    /**
     * "Monday to Friday", or "Mon–Fri" when $short. A run of consecutive days
     * is written as a range; anything else is listed.
     */
    public function dayRange(bool $short = false): string
    {
        $name = fn (int $day) => $short ? substr(self::DAY_NAMES[$day], 0, 3) : self::DAY_NAMES[$day];

        if ($this->days === []) {
            return $short ? 'By appointment' : 'By appointment only';
        }

        if (count($this->days) === 1) {
            return $name($this->days[0]);
        }

        // Indexed rather than end(): end() moves the array pointer, which PHP
        // treats as modifying a readonly property.
        $first = $this->days[0];
        $last = $this->days[count($this->days) - 1];

        if ($this->days === range($first, $last)) {
            return $name($first).($short ? '–' : ' to ').$name($last);
        }

        return collect($this->days)->map($name)->implode(', ');
    }

    /** "8:00 AM – 5:00 PM, Monday to Friday" — the form every sentence uses. */
    public function label(): string
    {
        return $this->timeRange().', '.$this->dayRange();
    }

    /**
     * Whether the room still opens, or is open, on the day of $at. A working
     * day counts as open until closing time; after that, and on days off, it
     * is closed.
     */
    public function isOpenToday(?CarbonInterface $at = null): bool
    {
        $at = $at ? Carbon::instance($at) : Carbon::now();

        if (! in_array($at->dayOfWeekIso, $this->days, true)) {
            return false;
        }

        return $at->lt($at->copy()->setTimeFromTimeString($this->close));
    }

    private function format(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time)->format('g:i A');
    }
}
