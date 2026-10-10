<?php

namespace Functional\Inspection\Faker;

use Xefi\Faker\Providers\Provider;

class InspectionFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            InspectionFakerExtension::class,
        ]);
    }
}
