<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferTransition;
use Functional\Sales\Exceptions\IllegalOfferTransitionException;

trait RefusesOfferTransitions
{
    public function accept(): OfferState
    {
        throw IllegalOfferTransitionException::for($this, OfferTransition::Accept);
    }

    public function reject(): OfferState
    {
        throw IllegalOfferTransitionException::for($this, OfferTransition::Reject);
    }

    public function withdraw(): OfferState
    {
        throw IllegalOfferTransitionException::for($this, OfferTransition::Withdraw);
    }
}
