<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferStatus;

final class AcceptedOfferState implements OfferState
{
    use RefusesOfferTransitions;

    public function status(): OfferStatus
    {
        return OfferStatus::Accepted;
    }

    public function withdraw(): OfferState
    {
        return new WithdrawnOfferState;
    }
}
