<?php

namespace Functional\Billing\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Billing\Periods\DateRange;
use Functional\Billing\Periods\PeriodSplitter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PeriodSplitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rentals(): iterable
    {
        yield 'same day' => ['2026-11-10', '2026-11-10'];
        yield 'within a month' => ['2026-11-10', '2026-11-14'];
        yield 'return on month end' => ['2026-11-20', '2026-11-30'];
        yield 'departure on month end' => ['2026-11-30', '2026-12-02'];
        yield 'over new year' => ['2026-12-20', '2027-01-05'];
        yield 'over a leap day' => ['2028-02-10', '2028-03-03'];
        yield 'over several months' => ['2026-08-15', '2026-12-10'];
    }

    #[DataProvider('rentals')]
    public function test_periods_cover_every_rental_day_exactly_once(string $departureDate, string $returnDate): void
    {
        $departure = CarbonImmutable::parse($departureDate);
        $return = CarbonImmutable::parse($returnDate);
        $splitter = new PeriodSplitter;

        $periods = $splitter->intermediatePeriods($departure, $return);
        $finalStart = $periods === [] ? $departure : end($periods)->end->addDay();
        $periods[] = new DateRange($finalStart, $return);

        $coveredDays = [];
        foreach ($periods as $period) {
            for ($day = $period->start; $day->lte($period->end); $day = $day->addDay()) {
                $coveredDays[] = $day->toDateString();
            }
        }

        $this->assertSame((int) $departure->diffInDays($return) + 1, count($coveredDays));
        $this->assertSame($coveredDays, array_values(array_unique($coveredDays)));
    }

    public function test_a_month_is_cut_only_once_its_last_day_has_passed(): void
    {
        $splitter = new PeriodSplitter;

        $this->assertSame([], $splitter->intermediatePeriods(CarbonImmutable::parse('2026-11-20'), CarbonImmutable::parse('2026-11-30')));
        $this->assertCount(1, $splitter->intermediatePeriods(CarbonImmutable::parse('2026-11-20'), CarbonImmutable::parse('2026-12-01')));
    }
}
