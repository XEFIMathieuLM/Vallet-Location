<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleStatus;

interface SaleState
{
    public function status(): SaleStatus;

    public function isOpen(): bool;

    public function acceptsOffers(): bool;

    public function acceptsAskingPriceChange(): bool;

    public function acceptsDescriptionChange(): bool;

    public function reserve(): SaleState;

    public function release(): SaleState;

    public function sell(): SaleState;

    public function cancel(): SaleState;
}
