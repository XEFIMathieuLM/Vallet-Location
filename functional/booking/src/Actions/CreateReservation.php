<?php

namespace Functional\Booking\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Eligibility\MachineEligibility;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Exceptions\InvalidReservationDatesException;
use Functional\Booking\Exceptions\ReservationOverlapException;
use Functional\Booking\Extensions\ReservationRequestGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateReservation
{
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(
        private readonly MachineEligibility $machineEligibility,
        private readonly ReservationRequestGuards $reservationRequestGuards,
    ) {}

    public function handle(Authenticatable&AgencyMember $author, Machine $machine, Customer|NewCustomer $customer, CarbonImmutable $startDate, CarbonImmutable $endDate): Reservation
    {
        $this->ensureDatesAreConsistent($startDate, $endDate);

        $reservation = rescue(
            fn (): Reservation => DB::transaction(fn (): Reservation => $this->reserve($author, $machine, $customer, $startDate, $endDate)),
            fn (Throwable $exception) => throw $this->translateDatabaseRefusal($exception),
            report: false,
        );

        ReservationChanged::dispatch($reservation);

        return $reservation;
    }

    private function ensureDatesAreConsistent(CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        if ($startDate->startOfDay()->lt(CarbonImmutable::today())) {
            throw InvalidReservationDatesException::startInThePast($startDate);
        }

        if ($endDate->lt($startDate)) {
            throw InvalidReservationDatesException::endBeforeStart($startDate, $endDate);
        }
    }

    private function reserve(Authenticatable&AgencyMember $author, Machine $machine, Customer|NewCustomer $customer, CarbonImmutable $startDate, CarbonImmutable $endDate): Reservation
    {
        $lockedMachine = Machine::query()->whereKey($machine->id)->lockForUpdate()->firstOrFail();

        $this->machineEligibility->ensureReservableUntil($lockedMachine, $endDate);

        foreach ($this->reservationRequestGuards->all() as $reservationRequestGuard) {
            $reservationRequestGuard->ensureCanReserve($lockedMachine, $startDate, $endDate);
        }

        $conflictingReservation = Reservation::query()
            ->with('agency')
            ->whereBelongsTo($lockedMachine)
            ->where('status', '<>', ReservationStatus::Cancelled)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->first();

        if ($conflictingReservation !== null) {
            throw ReservationOverlapException::with($conflictingReservation);
        }

        return Reservation::query()->create([
            'machine_id' => $lockedMachine->id,
            'customer_id' => $this->persistedCustomer($customer)->id,
            'agency_id' => $author->agencyId(),
            'created_by' => $author->getAuthIdentifier(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'planned_end_date' => $endDate,
            'status' => ReservationStatus::Confirmed,
        ]);
    }

    private function persistedCustomer(Customer|NewCustomer $customer): Customer
    {
        if ($customer instanceof Customer) {
            return $customer;
        }

        return Customer::query()->create([
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
        ]);
    }

    private function translateDatabaseRefusal(Throwable $exception): Throwable
    {
        if ($exception instanceof QueryException && $exception->getCode() === self::EXCLUSION_VIOLATION) {
            return ReservationOverlapException::concurrent();
        }

        return $exception;
    }
}
