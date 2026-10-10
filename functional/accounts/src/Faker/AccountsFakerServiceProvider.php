<?php

namespace Functional\Accounts\Faker;

use Xefi\Faker\Providers\Provider;

class AccountsFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            AccountsFakerExtension::class,
        ]);
    }
}
