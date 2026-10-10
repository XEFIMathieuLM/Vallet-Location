<?php

namespace Functional\Accounts\Tests\Unit;

use Functional\Accounts\Enums\PurchaseOrderSectionStatus;
use Functional\Accounts\Support\PurchaseOrderSectionState;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PurchaseOrderSectionStateTest extends TestCase
{
    /**
     * @return iterable<string, array{CustomerType|null, bool, bool, ReservationStatus, PurchaseOrderSectionStatus, bool}>
     */
    public static function situations(): iterable
    {
        yield 'individual' => [CustomerType::Individual, false, false, ReservationStatus::Confirmed, PurchaseOrderSectionStatus::Hidden, true];
        yield 'type to fill in' => [null, false, false, ReservationStatus::Confirmed, PurchaseOrderSectionStatus::Hidden, true];
        yield 'ordinary professional' => [CustomerType::Professional, false, false, ReservationStatus::Confirmed, PurchaseOrderSectionStatus::Optional, true];
        yield 'key account without number' => [CustomerType::Professional, true, false, ReservationStatus::Confirmed, PurchaseOrderSectionStatus::Required, false];
        yield 'number on a confirmed reservation' => [CustomerType::Professional, true, true, ReservationStatus::Confirmed, PurchaseOrderSectionStatus::Entered, true];
        yield 'number on a rental in progress' => [CustomerType::Professional, true, true, ReservationStatus::InProgress, PurchaseOrderSectionStatus::Frozen, true];
        yield 'number on a closed rental' => [CustomerType::Professional, false, true, ReservationStatus::Closed, PurchaseOrderSectionStatus::Frozen, true];
    }

    #[DataProvider('situations')]
    public function test_the_section_state_follows_the_data_model_table(
        ?CustomerType $customerType,
        bool $isKeyAccount,
        bool $hasNumber,
        ReservationStatus $reservationStatus,
        PurchaseOrderSectionStatus $expectedStatus,
        bool $isReadyForDeparture,
    ): void {
        $status = PurchaseOrderSectionState::resolve($customerType, $isKeyAccount, $hasNumber, $reservationStatus);

        $this->assertSame($expectedStatus, $status);
        $this->assertSame($isReadyForDeparture, $status->isReadyForDeparture());
    }
}
