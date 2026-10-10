<?php

namespace Functional\Certification\Faker;

use Xefi\Faker\Providers\Provider;

class CertificationFakerServiceProvider extends Provider
{
    public function boot(): void
    {
        $this->extensions([
            CertificationFakerExtension::class,
        ]);
    }
}
