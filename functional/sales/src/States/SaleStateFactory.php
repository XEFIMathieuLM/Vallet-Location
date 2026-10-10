<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleStatus;

final class SaleStateFactory
{
    public static function fromStatus(SaleStatus $status): SaleState
    {
        return match ($status) {
            SaleStatus::Listed => new ListedSaleState,
            SaleStatus::Reserved => new ReservedSaleState,
            SaleStatus::Sold => new SoldSaleState,
            SaleStatus::Cancelled => new CancelledSaleState,
        };
    }
}
