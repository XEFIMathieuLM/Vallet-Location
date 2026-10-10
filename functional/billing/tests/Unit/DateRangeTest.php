<?php

namespace Functional\Billing\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Billing\Exceptions\InvalidDateRangeException;
use Functional\Billing\Periods\DateRange;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function test_a_range_counts_its_days_bounds_included(): void
    {
        $this->assertSame(1, (new DateRange(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-10')))->days());
        $this->assertSame(11, (new DateRange(CarbonImmutable::parse('2026-11-20'), CarbonImmutable::parse('2026-11-30')))->days());
        $this->assertSame(31, (new DateRange(CarbonImmutable::parse('2028-02-10'), CarbonImmutable::parse('2028-03-11')))->days());
    }

    public function test_a_range_ending_before_it_starts_is_refused(): void
    {
        $this->expectException(InvalidDateRangeException::class);

        new DateRange(CarbonImmutable::parse('2026-11-14'), CarbonImmutable::parse('2026-11-13'));
    }
}
