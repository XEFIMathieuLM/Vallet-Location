<?php

namespace Functional\Booking\Tests\Concerns;

use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;

trait WithoutTransitionExtensions
{
    protected function setUpWithoutTransitionExtensions(): void
    {
        $this->app->instance(ReservationTransitionGuards::class, new ReservationTransitionGuards);
        $this->app->instance(ReservationDetailSections::class, new ReservationDetailSections);
    }
}
