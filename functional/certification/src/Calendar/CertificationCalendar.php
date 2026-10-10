<?php

namespace Functional\Certification\Calendar;

use Carbon\CarbonImmutable;
use Functional\Certification\Exceptions\MissingGoLiveDateException;

final class CertificationCalendar
{
    public function hasGoLiveDate(): bool
    {
        $configuredDate = config('certification.go_live_date');

        return is_string($configuredDate) && CarbonImmutable::canBeCreatedFromFormat($configuredDate, 'Y-m-d');
    }

    public function goLiveDate(): CarbonImmutable
    {
        if (! $this->hasGoLiveDate()) {
            throw MissingGoLiveDateException::make();
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', config()->string('certification.go_live_date'), $this->timezone()) ?: throw MissingGoLiveDateException::make();
    }

    public function isLive(): bool
    {
        return $this->hasGoLiveDate() && $this->today()->gte($this->goLiveDate());
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now()->setTimezone($this->timezone())->startOfDay();
    }

    private function timezone(): string
    {
        return config()->string('certification.timezone');
    }
}
