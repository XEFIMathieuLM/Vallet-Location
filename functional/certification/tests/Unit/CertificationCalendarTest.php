<?php

namespace Functional\Certification\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Exceptions\MissingGoLiveDateException;
use Tests\TestCase;

class CertificationCalendarTest extends TestCase
{
    public function test_the_feature_is_live_from_the_go_live_day_in_paris(): void
    {
        config(['certification.go_live_date' => '2026-11-02']);
        $calendar = new CertificationCalendar;

        $this->travelTo(CarbonImmutable::parse('2026-11-01 22:59:00', 'UTC'));
        $this->assertFalse($calendar->isLive());

        $this->travelTo(CarbonImmutable::parse('2026-11-01 23:00:00', 'UTC'));
        $this->assertTrue($calendar->isLive());
    }

    public function test_without_a_valid_date_the_feature_is_not_live_and_the_date_cannot_be_read(): void
    {
        config(['certification.go_live_date' => 'demain']);
        $calendar = new CertificationCalendar;

        $this->assertFalse($calendar->hasGoLiveDate());
        $this->assertFalse($calendar->isLive());
        $this->expectException(MissingGoLiveDateException::class);
        $calendar->goLiveDate();
    }
}
