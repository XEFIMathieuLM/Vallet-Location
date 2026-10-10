<?php

namespace Functional\Fleet\Faker;

use Xefi\Faker\Providers\Provider;

class FleetFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            FleetFakerExtension::class,
        ]);
    }
}
