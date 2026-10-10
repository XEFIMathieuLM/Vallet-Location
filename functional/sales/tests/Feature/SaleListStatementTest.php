<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Livewire\SaleList;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleListStatementTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_the_list_of_a_month_for_an_agency_shows_each_sale_and_the_total_of_concluded_sales(): void
    {
        $rouen = Agency::factory()->create(['name' => 'Rouen']);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00'));
        $firstSale = $this->soldSale(Machine::factory()->for($rouen)->create(['reference' => 'NAC-0001']), 1650000);
        $this->soldSale(Machine::factory()->for($rouen)->create(['reference' => 'NAC-0002']), 900000);
        $this->soldSale($this->machineForSale('MP-0003'), 500000);
        Transmission::factory()->forSource(BillableLineType::UsedMachineSale, $firstSale->id)->sent()->create();
        $this->travelTo(CarbonImmutable::parse('2026-12-05 10:00'));

        Livewire::actingAs($this->employee())->test(SaleList::class)
            ->set('agencyId', $rouen->id)
            ->set('periodStart', '2026-11-01')
            ->set('periodEnd', '2026-11-30')
            ->assertSee('NAC-0001')->assertSee('NAC-0002')->assertDontSee('MP-0003')
            ->assertSee('Transmis')
            ->assertSee('25500,00');
    }

    public function test_a_reserved_sale_past_its_planned_handover_date_is_highlighted(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00'));
        $this->reservedSale('2026-11-12', $this->machineForSale('NAC-0042'));
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));

        Livewire::actingAs($this->employee())->test(SaleList::class)->assertSee('Remise en retard');
    }

    private function soldSale(Machine $machine, int $finalPriceInCents): Sale
    {
        return Sale::factory()->sold()->create(['machine_id' => $machine->id, 'asking_price' => Money::fromStored($finalPriceInCents)]);
    }
}
