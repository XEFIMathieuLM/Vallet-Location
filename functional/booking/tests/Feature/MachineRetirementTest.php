<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MachineRetirementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{ReservationStatus}>
     */
    public static function activeStatuses(): array
    {
        return ['confirmed' => [ReservationStatus::Confirmed], 'in progress' => [ReservationStatus::InProgress]];
    }

    #[DataProvider('activeStatuses')]
    public function test_a_machine_with_an_active_reservation_cannot_be_retired(ReservationStatus $status): void
    {
        $machine = Machine::factory()->withStatus(MachineStatus::Workshop)->create(['reference' => 'NAC-0042']);
        $this->reservation($machine, $status);

        $this->expectException(MachineRetirementRefusedException::class);
        $this->expectExceptionMessage('NAC-0042');

        app(RetireMachine::class)->handle($machine);
    }

    public function test_a_machine_with_only_closed_or_cancelled_reservations_can_be_retired(): void
    {
        $machine = Machine::factory()->create();
        $this->reservation($machine, ReservationStatus::Closed);
        $this->reservation($machine, ReservationStatus::Cancelled);

        app(RetireMachine::class)->handle($machine);

        $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
    }

    private function reservation(Machine $machine, ReservationStatus $status): void
    {
        Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse('2030-11-10'), CarbonImmutable::parse('2030-11-14'))
            ->withStatus($status)
            ->create();
    }
}
