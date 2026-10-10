<?php

namespace Functional\Billing\Exports;

use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Lines\DamageLine;

final class ExportLineFormatter
{
    /**
     * @return array<string, string|int|null>
     */
    public function format(BillableLine $billableLine): array
    {
        $line = $billableLine->toArray();
        unset($line['amount_excl_tax_cents']);
        $line['amount_excl_tax'] = $billableLine instanceof DamageLine ? $billableLine->amountExclTax->format() : null;

        return $line;
    }
}
