<?php

namespace Functional\Accounts\Faker;

use Xefi\Faker\Extensions\Extension;

class AccountsFakerExtension extends Extension
{
    public function purchaseOrderNumber(): string
    {
        return $this->formatString('BC-{d}{d}{d}{d}-{d}{d}{d}{d}');
    }
}
