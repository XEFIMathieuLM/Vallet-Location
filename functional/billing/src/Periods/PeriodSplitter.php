<?php

namespace Functional\Billing\Periods;

use Carbon\CarbonImmutable;

final class PeriodSplitter
{
    /**
     * @return list<DateRange>
     */
    public function intermediatePeriods(CarbonImmutable $firstUncoveredDate, CarbonImmutable $today): array
    {
        $periods = [];
        $periodStart = $firstUncoveredDate;

        while ($periodStart->endOfMonth()->startOfDay()->lt($today)) {
            $periods[] = new DateRange($periodStart, $periodStart->endOfMonth()->startOfDay());
            $periodStart = $periodStart->endOfMonth()->startOfDay()->addDay();
        }

        return $periods;
    }
}
