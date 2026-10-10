<?php

namespace Functional\Portal\Pricing;

use NumberFormatter;

final class IndicativePriceFormatter
{
    private const CENTS_PER_EURO = 100;

    public function amount(int $amountCents): string
    {
        $currencyFormatter = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);

        return (string) $currencyFormatter->formatCurrency($amountCents / self::CENTS_PER_EURO, 'EUR');
    }

    public function centsFromEuros(string $typedAmount): ?int
    {
        $normalizedAmount = str_replace(',', '.', trim($typedAmount));

        if (preg_match('/^\d+(\.\d{1,2})?$/', $normalizedAmount) !== 1) {
            return null;
        }

        $amountCents = (int) round((float) $normalizedAmount * self::CENTS_PER_EURO);

        return $amountCents > 0 ? $amountCents : null;
    }
}
