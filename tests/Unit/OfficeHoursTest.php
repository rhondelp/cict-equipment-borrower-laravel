<?php

namespace Tests\Unit;

use App\Support\OfficeHours;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OfficeHoursTest extends TestCase
{
    private function weekdays(): OfficeHours
    {
        return new OfficeHours([1, 2, 3, 4, 5], '08:00', '17:00');
    }

    public function test_it_formats_the_time_range_and_days(): void
    {
        $hours = $this->weekdays();

        $this->assertSame('8:00 AM – 5:00 PM', $hours->timeRange());
        $this->assertSame('Monday to Friday', $hours->dayRange());
        $this->assertSame('Mon–Fri', $hours->dayRange(short: true));
        $this->assertSame('8:00 AM – 5:00 PM, Monday to Friday', $hours->label());
        $this->assertSame('5:00 PM', $hours->closingTime());
    }

    public function test_non_consecutive_days_are_listed(): void
    {
        $hours = new OfficeHours([1, 3, 5], '08:00', '17:00');

        $this->assertSame('Monday, Wednesday, Friday', $hours->dayRange());
        $this->assertSame('Mon, Wed, Fri', $hours->dayRange(short: true));
    }

    public function test_open_today_on_a_working_day_until_closing_time(): void
    {
        $hours = $this->weekdays();

        $this->assertTrue($hours->isOpenToday(Carbon::parse('2026-09-28 07:00'))); // Mon, before opening
        $this->assertTrue($hours->isOpenToday(Carbon::parse('2026-09-28 16:59')));
        $this->assertFalse($hours->isOpenToday(Carbon::parse('2026-09-28 17:00')));  // closed from 5 PM
        $this->assertFalse($hours->isOpenToday(Carbon::parse('2026-09-26 10:00')));  // Saturday
        $this->assertFalse($hours->isOpenToday(Carbon::parse('2026-09-27 10:00')));  // Sunday
    }
}
