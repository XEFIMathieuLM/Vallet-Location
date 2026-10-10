<?php

namespace Functional\Accounts\Support;

use Functional\Accounts\Enums\PurchaseOrderSectionStatus;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;

final class PurchaseOrderSectionState
{
    public static function resolve(?CustomerType $customerType, bool $isKeyAccount, bool $hasNumber, ReservationStatus $reservationStatus): PurchaseOrderSectionStatus
    {
        if ($customerType !== CustomerType::Professional && ! $hasNumber) {
            return PurchaseOrderSectionStatus::Hidden;
        }

        if ($reservationStatus !== ReservationStatus::Confirmed) {
            return PurchaseOrderSectionStatus::Frozen;
        }

        if ($hasNumber) {
            return PurchaseOrderSectionStatus::Entered;
        }

        return $isKeyAccount ? PurchaseOrderSectionStatus::Required : PurchaseOrderSectionStatus::Optional;
    }
}
