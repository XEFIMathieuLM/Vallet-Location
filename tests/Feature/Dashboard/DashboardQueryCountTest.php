<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Inspection\Models\Damage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class DashboardQueryCountTest extends TestCase
{
    use BuildsDashboardFixtures, RefreshDatabase;

    private Agency $rouen;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $this->rouen = $this->agencyNamed('Rouen');
        $this->employee = $this->employeeOf($this->rouen);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_number_of_items(): void
    {
        $this->addItemsToEverySection(2);
        $this->queriesToRender('');
        $fewItemsQueries = [$this->queriesToRender(''), $this->queriesToRender('toutes')];

        $this->addItemsToEverySection(28);

        $this->assertSame($fewItemsQueries, [$this->queriesToRender(''), $this->queriesToRender('toutes')]);
    }

    public function test_the_whole_network_renders_in_less_than_two_seconds_with_a_year_of_reservations(): void
    {
        $this->seedAYearOfActivity();
        $this->queriesToRender('toutes');

        $startedAt = microtime(true);
        $this->actingAs($this->employee)->get(route('dashboard', ['agence' => 'toutes']))->assertOk();

        $this->assertLessThan(2.0, microtime(true) - $startedAt);
    }

    private function addItemsToEverySection(int $itemsCount): void
    {
        Collection::times($itemsCount, function (): void {
            $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');
            $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-13', '2026-10-14');
            $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-01', '2026-10-10');
            $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-01', '2026-10-05');
            $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-20', '2026-10-22', ['conflict_reason' => ConflictReason::MachineUnavailable]);
            Machine::factory()->for($this->rouen)->recycle($this->sharedCategory())->vgpExpired()->create();
            Deposit::factory()->recycle([$this->rouen, $this->sharedCategory()])->withStatus(DepositStatus::ToRefund)->create();
            Damage::factory()->recycle([$this->rouen, $this->sharedCategory()])->create();
        });
    }

    private function queriesToRender(string $agency): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->employee)->get(route('dashboard', array_filter(['agence' => $agency])))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    private function seedAYearOfActivity(): void
    {
        $agencies = collect(['Lyon Est', 'Villeurbanne', 'Grenoble', 'Saint-Étienne', 'Clermont-Ferrand', 'Annecy'])
            ->map(fn (string $agencyName): Agency => $this->agencyNamed($agencyName))
            ->push($this->rouen);
        $machines = Machine::factory()->count(400)->recycle($agencies)->recycle($this->sharedCategory())->create();
        $customer = Customer::factory()->create();
        $firstDay = CarbonImmutable::today()->subYear();

        $reservations = $machines->flatMap(fn (Machine $machine, int $position): array => array_map(fn (int $period): array => [
            'machine_id' => $machine->id,
            'customer_id' => $customer->id,
            'agency_id' => $machine->agency_id,
            'created_by' => $this->employee->id,
            'start_date' => $firstDay->addWeeks($period * 7)->addDays($position % 7)->toDateString(),
            'end_date' => $firstDay->addWeeks($period * 7)->addDays($position % 7 + 3)->toDateString(),
            'planned_end_date' => $firstDay->addWeeks($period * 7)->addDays($position % 7 + 3)->toDateString(),
            'status' => $firstDay->addWeeks($period * 7)->lt(CarbonImmutable::today()->subWeek()) ? ReservationStatus::Closed->value : ReservationStatus::Confirmed->value,
        ], range(0, 7)));

        $reservations->chunk(500)->each(fn ($reservationRows) => DB::table('reservations')->insert($reservationRows->values()->all()));
        $this->assertGreaterThanOrEqual(3000, DB::table('reservations')->count());
    }
}
