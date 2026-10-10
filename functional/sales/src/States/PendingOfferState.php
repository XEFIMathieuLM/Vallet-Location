<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferStatus;

final class PendingOfferState implements OfferState
{
    use RefusesOfferTransitions;

    public function status(): OfferStatus
    {
        return OfferStatus::Pending;
    }

    public function accept(): OfferState
    {
        return new AcceptedOfferState;
    }

    public function reject(): OfferState
    {
        return new RejectedOfferState;
    }

    public function withdraw(): OfferState
    {
        return new WithdrawnOfferState;
    }
}
