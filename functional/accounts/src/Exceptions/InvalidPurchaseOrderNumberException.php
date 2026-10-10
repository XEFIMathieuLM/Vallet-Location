<?php

namespace Functional\Accounts\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidPurchaseOrderNumberException extends RefusalException
{
    public static function empty(): self
    {
        return new self('The purchase order number is empty.', 'accounts::refusals.purchase_order_empty');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(
            "The purchase order number is longer than {$maxLength} characters.",
            'accounts::refusals.purchase_order_too_long',
            ['max' => $maxLength],
        );
    }
}
