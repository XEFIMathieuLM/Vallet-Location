<?php

namespace Functional\Booking\Faker;

use Xefi\Faker\Providers\Provider;

class BookingFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            BookingFakerExtension::class,
        ]);
    }
}
