<?php

namespace Functional\Billing\Database\Seeders;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::query()->orderBy('id')->firstOrFail();
        $machines = Machine::query()->where('status', MachineStatus::Available)->orderBy('id')->limit(12)->get();
        $customersWithAccount = Customer::factory()->count(6)->create();
        $customersWithAccount->each(fn (Customer $customer) => CustomerBillingAccount::factory()->for($customer)->create());
        $customersWithoutAccount = Customer::factory()->count(2)->create();

        $sentRentals = $machines->take(6)->values()->map(fn (Machine $machine, int $position): Reservation => $this->closedRental($machine, $customersWithAccount[$position], 30 - $position * 4));
        $sentRentals->each(fn (Reservation $reservation) => Transmission::factory()->sent()->create(['billable_period_id' => $this->finalPeriod($reservation)->id, 'reservation_id' => $reservation->id]));

        $runningRental = $this->runningRental($machines[6], $customersWithAccount[0]);
        Transmission::factory()->sent()->create(['billable_period_id' => $this->intermediatePeriod($runningRental)->id, 'reservation_id' => $runningRental->id]);

        $this->transmissionsToHandle($machines->slice(7)->values(), $customersWithAccount, $customersWithoutAccount, $employee);
        $this->damages($sentRentals, $employee);
    }

    /**
     * @param  Collection<int, Machine>  $machines
     * @param  Collection<int, Customer>  $customersWithAccount
     * @param  Collection<int, Customer>  $customersWithoutAccount
     */
    private function transmissionsToHandle(Collection $machines, Collection $customersWithAccount, Collection $customersWithoutAccount, User $employee): void
    {
        $recentPending = $this->closedRental($machines[0], $customersWithAccount[1], 1);
        Transmission::factory()->create(['billable_period_id' => $this->finalPeriod($recentPending)->id, 'reservation_id' => $recentPending->id]);

        $oldPending = $this->closedRental($machines[1], $customersWithAccount[2], 3);
        Transmission::factory()->create(['billable_period_id' => $this->finalPeriod($oldPending)->id, 'reservation_id' => $oldPending->id, 'created_at' => CarbonImmutable::now()->subDays(2)]);

        $unknownCustomer = $this->closedRental($machines[2], $customersWithoutAccount[0], 4);
        Transmission::factory()->failed(TransmissionFailureReason::CustomerUnknown, TransmissionFailureReason::CustomerUnknown->label())
            ->create(['billable_period_id' => $this->finalPeriod($unknownCustomer)->id, 'reservation_id' => $unknownCustomer->id]);

        $rejected = $this->closedRental($machines[3], $customersWithAccount[3], 5);
        Transmission::factory()->failed(TransmissionFailureReason::Rejected, faker()->sentences(1))
            ->create(['billable_period_id' => $this->finalPeriod($rejected)->id, 'reservation_id' => $rejected->id]);

        $exported = $this->closedRental($machines[4], $customersWithoutAccount[1], 12);
        Transmission::factory()->exported()
            ->for(BillingExport::factory()->for($employee, 'creator')->state(['line_count' => 1]), 'export')
            ->create(['billable_period_id' => $this->finalPeriod($exported)->id, 'reservation_id' => $exported->id]);
    }

    /**
     * @param  Collection<int, Reservation>  $rentals
     */
    private function damages(Collection $rentals, User $employee): void
    {
        $billedDamage = $this->damage($rentals[0], resolvedBy: $employee);
        $billedSettlement = DamageSettlement::factory()->billed(faker()->number(5000, 120000), faker()->sentences(1))->for($billedDamage)->for($employee, 'settler')->create();
        Transmission::factory()->sent()->create(['billable_period_id' => null, 'damage_settlement_id' => $billedSettlement->id, 'reservation_id' => $rentals[0]->id]);

        $waivedDamage = $this->damage($rentals[1], resolvedBy: $employee);
        DamageSettlement::factory()->waived(faker()->sentences(1))->for($waivedDamage)->for($employee, 'settler')->create();

        $this->damage($rentals[2], reportedDaysAgo: 2);
        $this->damage($rentals[3], reportedDaysAgo: 10);
    }

    private function damage(Reservation $reservation, int $reportedDaysAgo = 1, ?User $resolvedBy = null): Damage
    {
        $views = ReservationView::factory()->count(3)->for($reservation)->state(new Sequence(['position' => 1], ['position' => 2], ['position' => 3]))->create();

        return Damage::factory()->for($reservation)->for($views->first(), 'view')->create([
            'reported_at' => CarbonImmutable::now()->subDays($reportedDaysAgo),
            'resolved_by' => $resolvedBy?->id,
            'resolved_at' => $resolvedBy === null ? null : CarbonImmutable::now(),
        ]);
    }

    private function closedRental(Machine $machine, Customer $customer, int $returnedDaysAgo): Reservation
    {
        $returnedAt = CarbonImmutable::now()->subDays($returnedDaysAgo)->setTime(17, 0);
        $departedAt = $returnedAt->subDays(faker()->number(1, 6))->setTime(8, 0);

        return Reservation::factory()->for($machine)->for($customer)
            ->between($departedAt->startOfDay(), $returnedAt->startOfDay())
            ->withStatus(ReservationStatus::Closed)
            ->create(['agency_id' => $machine->agency_id, 'departed_at' => $departedAt, 'returned_at' => $returnedAt]);
    }

    private function runningRental(Machine $machine, Customer $customer): Reservation
    {
        $departedAt = CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth()->addDays(9)->setTime(8, 0);
        $machine->update(['status' => MachineStatus::RentedOut]);

        return Reservation::factory()->for($machine)->for($customer)
            ->between($departedAt->startOfDay(), CarbonImmutable::today()->addDays(10))
            ->withStatus(ReservationStatus::InProgress)
            ->create(['agency_id' => $machine->agency_id, 'departed_at' => $departedAt]);
    }

    private function finalPeriod(Reservation $reservation): BillablePeriod
    {
        return BillablePeriod::factory()->for($reservation)
            ->between($reservation->start_date, $reservation->end_date, BillablePeriodKind::Final)
            ->create();
    }

    private function intermediatePeriod(Reservation $reservation): BillablePeriod
    {
        return BillablePeriod::factory()->for($reservation)
            ->between($reservation->start_date, $reservation->start_date->endOfMonth()->startOfDay(), BillablePeriodKind::Intermediate)
            ->create();
    }
}
