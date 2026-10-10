<?php

namespace Functional\Deposit\Database\Seeders;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DepositDemoSeeder extends Seeder
{
    private const AMOUNTS_IN_CENTS = [300000, 80000];

    private const DEMO_DEPOSITS = [
        [ReservationStatus::Confirmed, DepositStatus::Collected, 0],
        [ReservationStatus::Closed, DepositStatus::ToRefund, 2],
        [ReservationStatus::Closed, DepositStatus::ToRefund, 9],
        [ReservationStatus::Closed, DepositStatus::BlockedByDamage, 3],
        [ReservationStatus::Closed, DepositStatus::ToSettle, 1],
        [ReservationStatus::Closed, DepositStatus::Refunded, 15],
        [ReservationStatus::Cancelled, DepositStatus::ToRefund, 4],
    ];

    public function run(): void
    {
        $employee = $this->userModel()::query()->orderBy('id')->firstOrFail();
        $agencies = Agency::all();
        $categories = MachineCategory::all();

        MachineCategory::query()->orderBy('id')->limit(count(self::AMOUNTS_IN_CENTS))->get()
            ->each(fn (MachineCategory $category, int $position) => DepositRate::factory()->forCategory($category)->create([
                'amount' => Money::fromStored(self::AMOUNTS_IN_CENTS[$position]),
                'updated_by' => $employee->getKey(),
            ]));

        foreach (self::DEMO_DEPOSITS as [$reservationStatus, $depositStatus, $daysAgo]) {
            $reservation = Reservation::factory()
                ->recycle($agencies)
                ->for(Customer::factory()->individual())
                ->for(Machine::factory()->recycle($agencies)->recycle($categories))
                ->withStatus($reservationStatus)
                ->create(['created_by' => $employee->getKey()]);

            Deposit::factory()->recycle($agencies)->for($reservation)->withStatus($depositStatus)->create([
                'collected_by' => $employee->getKey(),
                ...($depositStatus->isFinal() ? ['closed_by' => $employee->getKey()] : []),
                'collected_agency_id' => $reservation->agency_id,
                'awaiting_since' => $depositStatus->isAwaitingAction() ? CarbonImmutable::now()->subDays($daysAgo) : null,
            ]);
        }
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
