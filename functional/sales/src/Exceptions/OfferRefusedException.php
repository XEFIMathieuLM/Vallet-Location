<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Models\Sale;

final class OfferRefusedException extends RefusalException
{
    public static function saleNotListed(Sale $sale): self
    {
        return new self(
            "Sale {$sale->id} does not accept offers in status {$sale->status->value}.",
            'sales::refusals.offer_sale_not_listed',
            ['status' => $sale->status],
        );
    }

    public static function nonPositiveAmount(): self
    {
        return new self('The amount of an offer must be strictly positive.', 'sales::refusals.offer_non_positive_amount');
    }

    public static function offeredInTheFuture(): self
    {
        return new self('An offer cannot be dated in the future.', 'sales::refusals.offer_in_the_future');
    }

    public static function concurrentAcceptance(): self
    {
        return new self('Acceptance rejected by the unique index: another offer of the sale was accepted concurrently.', 'sales::refusals.concurrent_acceptance');
    }
}
