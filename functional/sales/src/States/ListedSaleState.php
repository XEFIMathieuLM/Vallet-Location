<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleStatus;

final class ListedSaleState implements SaleState
{
    use RefusesSaleTransitions;

    public function status(): SaleStatus
    {
        return SaleStatus::Listed;
    }

    public function isOpen(): bool
    {
        return true;
    }

    public function acceptsOffers(): bool
    {
        return true;
    }

    public function acceptsAskingPriceChange(): bool
    {
        return true;
    }

    public function acceptsDescriptionChange(): bool
    {
        return true;
    }

    public function reserve(): SaleState
    {
        return new ReservedSaleState;
    }

    public function cancel(): SaleState
    {
        return new CancelledSaleState;
    }
}
