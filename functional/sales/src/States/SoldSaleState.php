<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleStatus;

final class SoldSaleState implements SaleState
{
    use RefusesSaleTransitions;

    public function status(): SaleStatus
    {
        return SaleStatus::Sold;
    }

    public function isOpen(): bool
    {
        return false;
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
        return false;
    }
}
