<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\DayOperations;
use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class AgencySelectionTest extends TestCase
{
    use BuildsDashboardFixtures, RefreshDatabase;

    private Agency $rouen;

    private Agency $evreux;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $this->rouen = $this->agencyNamed('Rouen');
        $this->evreux = $this->agencyNamed('Évreux');
        $this->employee = $this->employeeOf($this->rouen);
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-ROUEN']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');
        $this->reservationOf($this->machineIn($this->evreux, ['reference' => 'NAC-EVREUX']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');
    }

    public function test_the_dashboard_opens_on_the_agency_of_the_employee(): void
    {
        $this->assertSame($this->rouen->id, Livewire::actingAs($this->employee)->test(Dashboard::class)->get('agencyId'));
        $this->actingAs($this->employee)->get(route('dashboard'))->assertSee('NAC-ROUEN')->assertDontSee('NAC-EVREUX');
    }

    public function test_choosing_another_agency_shows_its_data_and_keeps_the_counters(): void
    {
        Deposit::factory()->withStatus(DepositStatus::ToRefund)->create();
        $dashboard = Livewire::actingAs($this->employee)->test(Dashboard::class)->set('agency', (string) $this->evreux->id);

        $this->assertSame($this->evreux->id, $dashboard->get('agencyId'));
        $this->actingAs($this->employee)->get(route('dashboard', ['agence' => $this->evreux->id]))
            ->assertSee('NAC-EVREUX')
            ->assertDontSee('NAC-ROUEN')
            ->assertSeeHtml('data-counter="deposits" data-highlighted="true"');
    }

    public function test_all_agencies_shows_every_agency_with_its_name_on_each_line(): void
    {
        $this->assertNull(Livewire::actingAs($this->employee)->test(Dashboard::class)->set('agency', 'toutes')->get('agencyId'));

        Livewire::actingAs($this->employee)->test(DayOperations::class, ['agencyId' => null])
            ->assertSee('NAC-ROUEN')
            ->assertSee('NAC-EVREUX')
            ->assertSee('· Rouen')
            ->assertSee('· Évreux');
    }

    public function test_the_agency_in_the_address_is_displayed(): void
    {
        $this->actingAs($this->employee)->get('/dashboard?agence='.$this->evreux->id)->assertSee('NAC-EVREUX')->assertDontSee('NAC-ROUEN');
    }

    public function test_an_unknown_agency_in_the_address_falls_back_to_the_employee_agency(): void
    {
        foreach (['999999', 'abc'] as $unknownAgency) {
            $this->actingAs($this->employee)->get('/dashboard?agence='.$unknownAgency)->assertOk()->assertSee('NAC-ROUEN')->assertDontSee('NAC-EVREUX');
        }
    }

    public function test_the_agency_selector_needs_a_permission_of_an_agency_section(): void
    {
        $this->actingAs($this->employee)->get(route('dashboard'))->assertSee(__('dashboard.agency.all'));

        $this->actingAs($this->memberOf($this->rouen))->get(route('dashboard'))
            ->assertDontSee(__('dashboard.agency.all'))
            ->assertSee(__('dashboard.empty.heading'));
    }
}
