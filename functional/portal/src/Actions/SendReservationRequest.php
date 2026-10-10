<?php

namespace Functional\Portal\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Exceptions\InvalidReservationDatesException;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\Exceptions\DuplicatePendingRequestException;
use Functional\Portal\Exceptions\PendingRequestLimitReachedException;
use Functional\Portal\Exceptions\RequestedMachineUnavailableException;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Queries\PortalMachineSearch;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendReservationRequest
{
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(
        private readonly PortalMachineSearch $portalMachineSearch,
        private readonly PortalHistory $portalHistory,
    ) {}

    public function handle(CustomerAccount $account, Machine $machine, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $comment): ReservationRequest
    {
        $this->ensureDatesAreConsistent($startDate, $endDate);

        $reservationRequest = rescue(
            fn (): ReservationRequest => DB::transaction(fn (): ReservationRequest => $this->send($account, $machine, $startDate, $endDate, $comment)),
            fn (Throwable $exception) => throw $this->translateDatabaseRefusal($exception),
            report: false,
        );

        ReservationRequestChanged::dispatch($reservationRequest);

        return $reservationRequest;
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

    private function send(CustomerAccount $account, Machine $machine, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $comment): ReservationRequest
    {
        CustomerAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
        $this->ensureBelowPendingLimit($account);

        if (! $this->portalMachineSearch->isOffered($machine, $startDate, $endDate)) {
            throw RequestedMachineUnavailableException::for($machine, $startDate, $endDate);
        }

        $reservationRequest = ReservationRequest::query()->create([
            'customer_account_id' => $account->id,
            'machine_id' => $machine->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'comment' => $comment === null || trim($comment) === '' ? null : trim($comment),
            'indicative_daily_price_cents' => $this->portalMachineSearch->dailyPriceCentsOf($machine->category),
            'status' => ReservationRequestStatus::Pending,
        ]);

        $this->portalHistory->record($reservationRequest, PortalHistoryEvent::RequestSent, $account, [
            'machine_reference' => $machine->reference,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'indicative_daily_price_cents' => $reservationRequest->indicative_daily_price_cents,
        ]);

        return $reservationRequest;
    }

    private function ensureBelowPendingLimit(CustomerAccount $account): void
    {
        $maxPendingRequests = config()->integer('portal.max_pending_requests');
        $pendingCount = ReservationRequest::query()->whereBelongsTo($account, 'account')->where('status', ReservationRequestStatus::Pending)->count();

        if ($pendingCount >= $maxPendingRequests) {
            throw PendingRequestLimitReachedException::of($maxPendingRequests);
        }
    }

    private function translateDatabaseRefusal(Throwable $exception): Throwable
    {
        if ($exception instanceof QueryException && $exception->getCode() === self::EXCLUSION_VIOLATION) {
            return DuplicatePendingRequestException::overlapping();
        }

        return $exception;
    }
}
