<?php

namespace Functional\Accounts\Database\Seeders;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class AccountsDemoSeeder extends Seeder
{
    private const DAYS_BEFORE_DEPARTURE_WITHOUT_NUMBER = [2, 8];

    private const DAYS_BEFORE_DEPARTURE_WITH_NUMBER = 5;

    public function run(): void
    {
        $employee = $this->employee();
        $keyAccounts = Customer::factory()->professional()->count(2)->create()->each(function (Customer $customer) use ($employee): void {
            CustomerBillingAccount::factory()->for($customer)->create();
            KeyAccount::factory()->for($customer)->create(['designated_by' => $employee->getKey()]);
        });
        $machines = Machine::factory()->count(3)->recycle(Agency::all())->recycle(MachineCategory::all())->create();

        foreach (self::DAYS_BEFORE_DEPARTURE_WITHOUT_NUMBER as $position => $daysBeforeDeparture) {
            $this->reservation($machines[$position], $keyAccounts[$position], $daysBeforeDeparture, $employee);
        }

        $reservationWithNumber = $this->reservation($machines[2], $keyAccounts[0], self::DAYS_BEFORE_DEPARTURE_WITH_NUMBER, $employee);
        ReservationPurchaseOrder::factory()->for($reservationWithNumber)->create([
            'entered_by' => $employee->getKey(),
            'agency_id' => $employee->agencyId(),
        ]);
    }

    private function reservation(Machine $machine, Customer $customer, int $daysBeforeDeparture, Model&AgencyMember $employee): Reservation
    {
        $startDate = CarbonImmutable::today()->addDays($daysBeforeDeparture);

        return Reservation::factory()
            ->for($machine)
            ->for($customer)
            ->between($startDate, $startDate->addDays(3))
            ->create(['agency_id' => $employee->agencyId(), 'created_by' => $employee->getKey()]);
    }

    private function employee(): Model&AgencyMember
    {
        /** @var class-string<Model&AgencyMember> $userModel */
        $userModel = config('auth.providers.users.model');

        return $userModel::query()->orderBy('id')->firstOrFail();
    }
}
