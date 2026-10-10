<?php

namespace Functional\Inspection\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Inspection\Enums\InspectionStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InspectionStepTest extends TestCase
{
    /**
     * @return array<string, array{ReservationStatus, string, bool}>
     */
    public static function departureOpening(): array
    {
        return [
            'confirmed, starting today' => [ReservationStatus::Confirmed, '2026-10-10', true],
            'confirmed, started yesterday' => [ReservationStatus::Confirmed, '2026-10-09', true],
            'confirmed, starting tomorrow' => [ReservationStatus::Confirmed, '2026-10-11', false],
            'in progress' => [ReservationStatus::InProgress, '2026-10-10', false],
            'cancelled' => [ReservationStatus::Cancelled, '2026-10-10', false],
        ];
    }

    #[DataProvider('departureOpening')]
    public function test_the_departure_step_opens_on_a_confirmed_reservation_from_its_start_date(ReservationStatus $status, string $startDate, bool $isOpen): void
    {
        $this->assertSame($isOpen, InspectionStep::Departure->opensWhen($status, CarbonImmutable::parse($startDate), CarbonImmutable::parse('2026-10-10')));
    }

    public function test_the_return_step_opens_only_on_a_reservation_in_progress(): void
    {
        foreach (ReservationStatus::cases() as $status) {
            $this->assertSame(
                $status === ReservationStatus::InProgress,
                InspectionStep::Return->opensWhen($status, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-10')),
            );
        }
    }

    public function test_the_departure_step_is_validated_once_the_reservation_is_no_longer_confirmed(): void
    {
        foreach (ReservationStatus::cases() as $status) {
            $this->assertSame($status !== ReservationStatus::Confirmed, InspectionStep::Departure->isValidatedOnceReservationIs($status));
        }
    }

    public function test_the_return_step_is_validated_once_the_reservation_is_closed(): void
    {
        foreach (ReservationStatus::cases() as $status) {
            $this->assertSame($status === ReservationStatus::Closed, InspectionStep::Return->isValidatedOnceReservationIs($status));
        }
    }

    public function test_each_step_maps_to_its_reservation_transition(): void
    {
        $this->assertSame(ReservationTransition::Departure, InspectionStep::Departure->transition());
        $this->assertSame(ReservationTransition::Return, InspectionStep::Return->transition());
    }
}
