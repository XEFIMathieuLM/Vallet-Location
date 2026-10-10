<?php

namespace Functional\Billing\Faker;

use Xefi\Faker\Extensions\Extension;

class BillingExtension extends Extension
{
    public function billingCustomerRef(): string
    {
        return $this->formatString('CLI-{d}{d}{d}{d}{d}');
    }

    public function billingSoftwareRef(): string
    {
        return $this->formatString('FAC-{d}{d}{d}{d}{d}{d}');
    }

    public function billingExportFileName(): string
    {
        $exportedAt = $this->randomizer->getInt(strtotime('-1 year'), time());

        return 'export-facturation-'.date('Ymd-His', $exportedAt).'.csv';
    }
}
