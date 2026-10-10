<?php

namespace Functional\Sales\Tests\Feature;

use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Sales\Exceptions\MachineHasOpenSaleException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenSaleBlocksRetirementTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_a_machine_with_an_open_sale_cannot_be_retired_by_hand(): void
    {
        $listedSale = $this->listedSale($this->machineForSale('NAC-0001'));
        $reservedSale = $this->reservedSale('2026-12-20', $this->machineForSale('NAC-0002'));

        $this->assertRefused(MachineHasOpenSaleException::class, 'NAC-0001', fn () => app(RetireMachine::class)->handle($listedSale->machine));
        $this->assertRefused(MachineHasOpenSaleException::class, 'NAC-0002', fn () => app(RetireMachine::class)->handle($reservedSale->machine));
        $this->assertSame(MachineStatus::Available, $listedSale->machine->fresh()?->status);
    }

    public function test_once_the_sale_is_cancelled_the_machine_can_be_retired(): void
    {
        $sale = Sale::factory()->cancelled()->create();

        app(RetireMachine::class)->handle($sale->machine);

        $this->assertSame(MachineStatus::Retired, $sale->machine->fresh()?->status);
    }

    public function test_the_reservations_guard_of_booking_still_applies(): void
    {
        $machine = $this->machineForSale();
        $this->confirmedReservation($machine, '2030-01-10', '2030-01-12');

        $this->assertRefused(MachineRetirementRefusedException::class, 'réservation', fn () => app(RetireMachine::class)->handle($machine));
    }
}
