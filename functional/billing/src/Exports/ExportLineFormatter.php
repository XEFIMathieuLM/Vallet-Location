<?php

namespace Functional\Billing\Exports;

use Functional\Billing\Money\EuroAmount;
use Functional\Billing\ValueObjects\BillableLine;

final class ExportLineFormatter
{
    public function __construct(private readonly EuroAmount $euroAmount) {}

    /**
     * @return array<string, string|int|null>
     */
    public function format(BillableLine $billableLine): array
    {
        $line = $billableLine->toArray();
        unset($line['amount_excl_tax_cents']);
        $line['amount_excl_tax'] = $billableLine->amountExclTaxCents === null ? null : $this->euroAmount->format($billableLine->amountExclTaxCents);

        return $line;
    }
}
