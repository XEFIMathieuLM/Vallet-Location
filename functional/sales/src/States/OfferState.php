<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferStatus;

interface OfferState
{
    public function status(): OfferStatus;

    public function accept(): OfferState;

    public function reject(): OfferState;

    public function withdraw(): OfferState;
}
