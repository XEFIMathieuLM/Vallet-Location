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

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now()->setTimezone($this->timezone());
    }

    public function today(): CarbonImmutable
    {
        return $this->dateOf(CarbonImmutable::now());
    }

    public function hasGoLiveDate(): bool
    {
        $configuredDate = config('billing.go_live_date');

        return is_string($configuredDate) && CarbonImmutable::canBeCreatedFromFormat($configuredDate, 'Y-m-d');
    }

    public function goLiveDate(): CarbonImmutable
    {
        if (! $this->hasGoLiveDate()) {
            throw MissingGoLiveDateException::make();
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', config()->string('billing.go_live_date'), $this->timezone()) ?: throw MissingGoLiveDateException::make();
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
