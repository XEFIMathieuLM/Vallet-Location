<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferStatus;

final class WithdrawnOfferState implements OfferState
{
    use RefusesOfferTransitions;

    public function status(): OfferStatus
    {
        return OfferStatus::Withdrawn;
    }
}
