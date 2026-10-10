<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleStatus;

final class ReservedSaleState implements SaleState
{
    use RefusesSaleTransitions;

    public function status(): SaleStatus
    {
        return SaleStatus::Reserved;
    }

    public function isOpen(): bool
    {
        return true;
    }

    public function acceptsOffers(): bool
    {
        return false;
    }

    public function acceptsAskingPriceChange(): bool
    {
        return false;
    }

    public function acceptsDescriptionChange(): bool
    {
        return true;
    }

    public function release(): SaleState
    {
        return new ListedSaleState;
    }

    public function sell(): SaleState
    {
        return new SoldSaleState;
    }

    public function cancel(): SaleState
    {
        return new CancelledSaleState;
    }
}
