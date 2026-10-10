<?php

namespace Functional\Sales\Tests\Feature;

use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Sales\Livewire\SaleList;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleListScreenTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_every_agency_sees_the_machines_for_sale_with_price_agency_and_fleet_status(): void
    {
        $this->listedSale($this->machineForSale('NAC-0042'), 18000);

        $this->actingAs($this->employee())
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('NAC-0042')
            ->assertSee('18000,00')
            ->assertSee('En vente')
            ->assertSee('Disponible');
    }

    public function test_the_list_filters_by_category_home_agency_and_sale_status(): void
    {
        $nacelles = MachineCategory::factory()->create(['name' => 'Nacelles']);
        $rouen = Agency::factory()->create(['name' => 'Rouen']);
        $this->listedSale(Machine::factory()->for($nacelles, 'category')->for($rouen)->create(['reference' => 'NAC-0001']));
        $this->listedSale($this->machineForSale('MP-0002'));
        Sale::factory()->cancelled()->create(['machine_id' => $this->machineForSale('CH-0003')->id]);

        Livewire::actingAs($this->employee())->test(SaleList::class)
            ->assertSee('NAC-0001')->assertSee('MP-0002')->assertSee('CH-0003')
            ->set('categoryId', $nacelles->id)
            ->assertSee('NAC-0001')->assertDontSee('MP-0002')
            ->set('categoryId', null)
            ->set('agencyId', $rouen->id)
            ->assertSee('NAC-0001')->assertDontSee('MP-0002')
            ->set('agencyId', null)
            ->set('status', 'cancelled')
            ->assertSee('CH-0003')->assertDontSee('NAC-0001');
    }

    public function test_the_list_refreshes_when_a_sale_changes_in_another_agency(): void
    {
        $component = Livewire::actingAs($this->employee())->test(SaleList::class)->assertDontSee('NAC-0042');

        $this->listedSale($this->machineForSale('NAC-0042'));

        $component->dispatch('echo-private:sales,.sale.changed')->assertSee('NAC-0042');
    }

    public function test_the_sales_screens_are_refused_without_the_sales_permission(): void
    {
        $sale = $this->listedSale();

        $this->actingAs($this->userWithoutPermission())->get(route('sales.index'))->assertForbidden();
        $this->actingAs($this->userWithoutPermission())->get(route('sales.create'))->assertForbidden();
        $this->actingAs($this->userWithoutPermission())->get(route('sales.show', $sale))->assertForbidden();
    }
}
