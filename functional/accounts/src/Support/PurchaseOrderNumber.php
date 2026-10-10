<?php

namespace Functional\Accounts\Support;

use Functional\Accounts\Exceptions\InvalidPurchaseOrderNumberException;

final class PurchaseOrderNumber
{
    public static function normalize(string $rawNumber): string
    {
        $number = trim($rawNumber);
        $maxLength = config()->integer('accounts.purchase_order_max_length');

        if ($number === '') {
            throw InvalidPurchaseOrderNumberException::empty();
        }

        if (mb_strlen($number) > $maxLength) {
            throw InvalidPurchaseOrderNumberException::tooLong($maxLength);
        }

        return $number;
    }
}
