<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Sales\Enums\OfferTransition;
use Functional\Sales\States\OfferState;

final class IllegalOfferTransitionException extends RefusalException
{
    public static function for(OfferState $from, OfferTransition $transition): self
    {
        return new self(
            "Offer transition {$transition->value} is not allowed from status {$from->status()->value}.",
            'sales::refusals.illegal_offer_transition',
            ['transition' => $transition, 'status' => $from->status()],
        );
    }
}
