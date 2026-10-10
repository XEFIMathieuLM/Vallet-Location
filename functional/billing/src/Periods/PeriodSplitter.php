<?php

namespace Functional\Billing\Periods;

use Carbon\CarbonImmutable;

final class PeriodSplitter
{
    /**
     * @return list<array{CarbonImmutable, CarbonImmutable}>
     */
    public function intermediatePeriods(CarbonImmutable $firstUncoveredDate, CarbonImmutable $today): array
    {
        $periods = [];
        $periodStart = $firstUncoveredDate;

        while ($periodStart->endOfMonth()->startOfDay()->lt($today)) {
            $periodEnd = $periodStart->endOfMonth()->startOfDay();
            $periods[] = [$periodStart, $periodEnd];
            $periodStart = $periodEnd->addDay();
        }

        return $periods;
    }
}
