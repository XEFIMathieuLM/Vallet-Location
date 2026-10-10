<?php

namespace Functional\Booking\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Exceptions\InvalidReservationDatesException;
use Functional\Booking\Exceptions\ReservationOverlapException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateReservation
{
    private const EXCLUSION_VIOLATION = '23P01';

    public function handle(User $author, Machine $machine, Customer $customer, CarbonImmutable $startDate, CarbonImmutable $endDate): Reservation
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
            throw InvalidReservationDatesException::startInThePast();
        }

        if ($endDate->lt($startDate)) {
            throw InvalidReservationDatesException::endBeforeStart();
        }
    }

    private function reserve(User $author, Machine $machine, Customer $customer, CarbonImmutable $startDate, CarbonImmutable $endDate): Reservation
    {
        Machine::query()->whereKey($machine->id)->lockForUpdate()->firstOrFail();

        $conflictingReservation = Reservation::query()
            ->with('agency')
            ->where('machine_id', $machine->id)
            ->where('status', '<>', ReservationStatus::Cancelled)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->first();

        if ($conflictingReservation !== null) {
            throw ReservationOverlapException::with($conflictingReservation);
        }

        return Reservation::query()->create([
            'machine_id' => $machine->id,
            'customer_id' => $customer->id,
            'agency_id' => $author->agency_id,
            'created_by' => $author->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'planned_end_date' => $endDate,
            'status' => ReservationStatus::Confirmed,
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
