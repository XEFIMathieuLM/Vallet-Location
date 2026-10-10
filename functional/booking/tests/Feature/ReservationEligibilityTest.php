<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Exceptions\MachineNotReservableException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationEligibilityTest extends TestCase
{
    use AssertsRefusals, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
    }

    /**
     * @return array<string, array{MachineStatus, string}>
     */
    public static function unavailableStatuses(): array
    {
        return [
            'workshop' => [MachineStatus::Workshop, 'Atelier'],
            'out of order' => [MachineStatus::OutOfOrder, 'En panne'],
            'retired' => [MachineStatus::Retired, 'Retirée du parc'],
        ];
    }

    #[DataProvider('unavailableStatuses')]
    public function test_an_unavailable_machine_is_refused_with_its_status(MachineStatus $status, string $statusLabel): void
    {
        $machine = Machine::factory()->withStatus($status)->create();

        $this->assertRefused(MachineNotReservableException::class, "« {$statusLabel} »", fn () => $this->reserve($machine, '2026-11-10', '2026-11-14'));
    }

    public function test_a_rented_out_machine_can_be_reserved_after_its_current_rental(): void
    {
        $machine = Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        $this->currentRental($machine, '2026-10-28', '2026-11-05');

        $reservation = $this->reserve($machine, '2026-11-10', '2026-11-14');

        $this->assertTrue($reservation->exists);
    }

    public function test_a_late_machine_is_refused_for_any_period(): void
    {
        $machine = Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        $this->currentRental($machine, '2026-10-20', '2026-10-30');

        $this->assertRefused(MachineNotReservableException::class, __('booking::reservations.refusals.machine_not_returned'), fn () => $this->reserve($machine, '2026-12-10', '2026-12-14'));
    }

    public function test_a_machine_whose_vgp_expires_during_the_period_is_refused_with_the_due_date(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-12'))->create();

        $this->assertRefused(MachineNotReservableException::class, '12/11/2026', fn () => $this->reserve($machine, '2026-11-10', '2026-11-14'));
    }

    public function test_a_machine_whose_vgp_covers_the_period_is_accepted(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-30'))->create();

        $reservation = $this->reserve($machine, '2026-11-10', '2026-11-14');

        $this->assertTrue($reservation->exists);
    }

    public function test_a_vgp_expiring_on_the_end_date_covers_the_period(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-14'))->create();

        $reservation = $this->reserve($machine, '2026-11-10', '2026-11-14');

        $this->assertTrue($reservation->exists);
    }

    public function test_a_machine_subject_to_vgp_without_due_date_is_refused(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(null)->create();

        $this->assertRefused(MachineNotReservableException::class, __('booking::reservations.refusals.vgp_missing'), fn () => $this->reserve($machine, '2026-11-10', '2026-11-14'));
    }

    public function test_the_availability_search_hides_machines_that_cannot_be_reserved(): void
    {
        $availableMachine = Machine::factory()->create();
        $workshopMachine = Machine::factory()->withStatus(MachineStatus::Workshop)->create();
        $expiringVgpMachine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-12'))->create();
        $coveredVgpMachine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-30'))->create();
        $lateMachine = Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        $this->currentRental($lateMachine, '2026-10-20', '2026-10-30');
        $rentedOutMachine = Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        $this->currentRental($rentedOutMachine, '2026-10-28', '2026-11-05');

        $availableMachines = app(AvailableMachinesQuery::class)->get(
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-14'),
        );

        $this->assertEqualsCanonicalizing(
            [$availableMachine->id, $coveredVgpMachine->id, $rentedOutMachine->id],
            $availableMachines->modelKeys(),
        );
        $this->assertNotContains($workshopMachine->id, $availableMachines->modelKeys());
        $this->assertNotContains($expiringVgpMachine->id, $availableMachines->modelKeys());
        $this->assertNotContains($lateMachine->id, $availableMachines->modelKeys());
    }

    private function currentRental(Machine $machine, string $startDate, string $endDate): void
    {
        Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate))
            ->withStatus(ReservationStatus::InProgress)
            ->create();
    }

    private function reserve(Machine $machine, string $startDate, string $endDate): Reservation
    {
        return app(CreateReservation::class)->handle(
            User::factory()->employee()->create(),
            $machine,
            Customer::factory()->create(),
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        );
    }
}
