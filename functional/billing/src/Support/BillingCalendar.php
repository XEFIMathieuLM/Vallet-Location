<?php

namespace Functional\Billing\Support;

use Carbon\CarbonImmutable;
use Functional\Billing\Exceptions\MissingGoLiveDateException;

final class BillingCalendar
{
    public function dateOf(CarbonImmutable $moment): CarbonImmutable
    {
        return $moment->setTimezone($this->timezone())->startOfDay();
    }

    public function today(): CarbonImmutable
    {
        return $this->dateOf(CarbonImmutable::now());
    }

    public function goLiveDate(): CarbonImmutable
    {
        $configuredDate = config('billing.go_live_date');

        if (! is_string($configuredDate) || ! CarbonImmutable::canBeCreatedFromFormat($configuredDate, 'Y-m-d')) {
            throw MissingGoLiveDateException::make();
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $configuredDate, $this->timezone()) ?: throw MissingGoLiveDateException::make();
    }

    public function isLive(): bool
    {
        return $this->today()->gte($this->goLiveDate());
    }

    private function timezone(): string
    {
        return config()->string('billing.timezone');
    }
}
