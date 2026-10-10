<?php

namespace Functional\Billing\Support;

final class EuroAmount
{
    public const INPUT_PATTERN = '/^\d{1,7}([.,]\d{1,2})?$/';

    public function format(int $amountCents): string
    {
        return sprintf('%d,%02d', intdiv($amountCents, 100), $amountCents % 100);
    }

    public function toCents(string $typedAmount): int
    {
        [$euros, $cents] = array_pad(explode('.', str_replace(',', '.', trim($typedAmount))), 2, '0');

        return (int) $euros * 100 + (int) str_pad(substr($cents, 0, 2), 2, '0');
    }
}
