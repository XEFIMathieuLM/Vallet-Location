<?php

namespace Functional\Certification\Faker;

use Xefi\Faker\Extensions\Extension;

class CertificationFakerExtension extends Extension
{
    public function vgpReportFileName(): string
    {
        return $this->formatString('rapport-vgp-{d}{d}{d}{d}{d}.pdf');
    }
}
