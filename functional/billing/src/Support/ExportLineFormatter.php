<?php

namespace Functional\Billing\Support;

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
        $line['amount_excl_tax'] = $billableLine->amountExclTaxCents === null ? null : $this->euros($billableLine->amountExclTaxCents);

        return $line;
    }

    private function euros(int $amountCents): string
    {
        return sprintf('%d,%02d', intdiv($amountCents, 100), $amountCents % 100);
    }
}
