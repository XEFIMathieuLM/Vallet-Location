<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Data\PortalMachineOffer;
use Functional\Portal\Livewire\Customer\Search;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalSearchTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    private MachineCategory $aerialPlatforms;

    private Agency $rouen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->aerialPlatforms = MachineCategory::factory()->create(['name' => 'Nacelle']);
        $this->rouen = Agency::factory()->create(['name' => 'Rouen']);
        $this->actingAs($this->customerAccount(), 'customer');
    }

    public function test_only_available_machines_of_the_category_and_agency_are_offered_with_their_indicative_price(): void
    {
        CategoryIndicativePrice::factory()->for($this->aerialPlatforms, 'category')->create(['daily_price_cents' => 9500]);
        $free = $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-0001']);
        $taken = $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-0002']);
        Reservation::factory()->for($taken)->between(CarbonImmutable::parse('2026-11-12'), CarbonImmutable::parse('2026-11-20'))->create();
        $this->reservableMachine(MachineCategory::factory()->create(), $this->rouen, ['reference' => 'MPE-0001']);
        $this->reservableMachine($this->aerialPlatforms, Agency::factory()->create(), ['reference' => 'NAC-0003']);

        $this->search()
            ->assertSee('NAC-0001')
            ->assertSee('Nacelle')
            ->assertSee('Rouen')
            ->assertSee('à partir de 95,00', false)
            ->assertSee(__('portal::search.price_notice'))
            ->assertDontSee('NAC-0002')
            ->assertDontSee('MPE-0001')
            ->assertDontSee('NAC-0003');

        $this->assertSame([$free->id], $this->offeredIds());
    }

    public function test_unavailable_or_non_compliant_machines_are_never_offered(): void
    {
        $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-PANNE', 'status' => MachineStatus::OutOfOrder]);
        $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-ATELIER', 'status' => MachineStatus::Workshop]);
        $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-RETIREE', 'status' => MachineStatus::Retired]);
        $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-VGP', 'is_subject_to_vgp' => true, 'vgp_due_date' => '2026-11-12']);
        $partlyTaken = $this->reservableMachine($this->aerialPlatforms, $this->rouen, ['reference' => 'NAC-PRISE']);
        Reservation::factory()->for($partlyTaken)->between(CarbonImmutable::parse('2026-11-14'), CarbonImmutable::parse('2026-11-15'))->create();

        $this->assertSame([], $this->offeredIds());
        $this->search()->assertSee(__('portal::search.empty'));
    }

    public function test_the_results_never_show_other_customers_or_reservations(): void
    {
        $machine = $this->reservableMachine($this->aerialPlatforms, $this->rouen);
        $otherCustomer = Customer::factory()->create(['name' => 'Bâtiments Lefebvre']);
        Reservation::factory()->for($machine)->for($otherCustomer)->between(CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2026-12-05'))->create();

        $this->search()->assertSee($machine->reference)->assertDontSee('Bâtiments Lefebvre')->assertDontSee('01/12/2026');
    }

    public function test_the_customer_search_offers_exactly_the_machines_of_the_employee_search(): void
    {
        Machine::factory()->count(6)->for($this->aerialPlatforms, 'category')->for($this->rouen)->create();
        Machine::factory()->count(3)->for($this->aerialPlatforms, 'category')->for($this->rouen)->vgpExpiringSoon()->create();
        Machine::factory()->count(2)->for($this->aerialPlatforms, 'category')->for($this->rouen)->withStatus(MachineStatus::Workshop)->create();
        $reserved = Machine::factory()->for($this->aerialPlatforms, 'category')->for($this->rouen)->create();
        Reservation::factory()->for($reserved)->between(CarbonImmutable::parse('2026-10-20'), CarbonImmutable::parse('2026-10-22'))->create();

        foreach ([['2026-10-12', '2026-10-13'], ['2026-10-20', '2026-10-21'], ['2026-11-12', '2026-11-16']] as [$startDate, $endDate]) {
            $employeeIds = app(AvailableMachinesQuery::class)
                ->get(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate), $this->aerialPlatforms, $this->rouen)
                ->pluck('id')->all();

            $this->assertSame($employeeIds, $this->offeredIds($startDate, $endDate));
        }
    }

    public function test_a_category_without_indicative_price_shows_price_on_request(): void
    {
        $this->reservableMachine($this->aerialPlatforms, $this->rouen);

        $this->search()->assertSee(__('portal::search.price_on_request'));
    }

    public function test_all_criteria_are_required(): void
    {
        $this->reservableMachine($this->aerialPlatforms, $this->rouen);

        Livewire::test(Search::class)
            ->set('categoryId', $this->aerialPlatforms->id)
            ->assertSee(__('portal::search.criteria_required'))
            ->assertViewHas('offers', []);
    }

    private function search(string $startDate = '2026-11-12', string $endDate = '2026-11-16'): mixed
    {
        return Livewire::test(Search::class)
            ->set('categoryId', $this->aerialPlatforms->id)
            ->set('agencyId', $this->rouen->id)
            ->set('startDate', $startDate)
            ->set('endDate', $endDate);
    }

    /**
     * @return list<int>
     */
    private function offeredIds(string $startDate = '2026-11-12', string $endDate = '2026-11-16'): array
    {
        /** @var list<PortalMachineOffer> $offers */
        $offers = $this->search($startDate, $endDate)->viewData('offers');

        return array_map(fn (PortalMachineOffer $offer): int => $offer->machineId, $offers);
    }
}
