<?php

namespace Functional\Billing\Faker;

use Xefi\Faker\Providers\Provider;

class BillingFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            BillingFakerExtension::class,
        ]);
    }
}
