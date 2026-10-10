<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Livewire\Planning;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Livewire\MachineIndex;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleBadgesTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_a_machine_for_sale_is_marked_in_the_fleet_and_the_planning_with_a_link_to_its_sale(): void
    {
        $sale = $this->listedSale();
        $employee = $this->employee();

        Livewire::actingAs($employee)->test(MachineIndex::class)
            ->assertSee('En vente')
            ->assertSeeHtml(route('sales.show', $sale));
        Livewire::actingAs($employee)->withQueryParams(['du' => '2026-11-02', 'au' => '2026-11-08'])->test(Planning::class)
            ->assertSee('En vente');
    }

    public function test_a_reserved_sale_shows_its_handover_date_in_the_badge(): void
    {
        $this->reservedSale('2026-11-20');

        Livewire::actingAs($this->employee())->test(MachineIndex::class)->assertSee('Vendue sous réserve — remise le 20/11');
    }

    public function test_a_machine_for_sale_remains_reservable_for_rental(): void
    {
        $sale = $this->listedSale();

        $reservation = app(CreateReservation::class)->handle(
            $this->employee(),
            $sale->machine,
            Customer::factory()->create(),
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-14'),
        );

        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
    }

    public function test_the_fleet_and_planning_screens_refresh_when_a_sale_changes(): void
    {
        $this->listedSale();
        $employee = $this->employee();

        Livewire::actingAs($employee)->test(MachineIndex::class)->dispatch('echo-private:sales,.sale.changed')->assertOk()->assertSee('En vente');
        Livewire::actingAs($employee)->test(Planning::class)->dispatch('echo-private:sales,.sale.changed')->assertOk();
    }
}
