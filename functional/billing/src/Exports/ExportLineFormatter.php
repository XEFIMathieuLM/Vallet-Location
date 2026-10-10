<?php

namespace Functional\Billing\Exports;

use Functional\Billing\ValueObjects\BillableLine;

final class ExportLineFormatter
{
    /**
     * @return array<string, string|int|null>
     */
    public function format(BillableLine $billableLine): array
    {
        $line = $billableLine->toArray();
        unset($line['amount_excl_tax_cents']);
        $line['amount_excl_tax'] = $billableLine->amountExclTax?->format();

        return $line;
    }
}
