<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\HandoverConflictsWithReservationException;
use Functional\Sales\Exceptions\InvalidHandoverDateException;
use Functional\Sales\Exceptions\OfferRefusedException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Queries\HandoverConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AcceptOffer
{
    private const UNIQUE_VIOLATION = '23505';

    public function __construct(
        private readonly HandoverConflicts $handoverConflicts,
        private readonly SaleHistory $saleHistory,
    ) {}

    public function handle(Model&AgencyMember $author, SaleOffer $offer, CarbonImmutable $plannedHandoverDate): Sale
    {
        if ($plannedHandoverDate->startOfDay()->lt(CarbonImmutable::today())) {
            throw InvalidHandoverDateException::inThePast($plannedHandoverDate);
        }

        $sale = rescue(
            fn (): Sale => DB::transaction(fn (): Sale => $this->reserve($author, $offer, $plannedHandoverDate)),
            fn (Throwable $exception) => throw $this->translateDatabaseRefusal($exception),
            report: false,
        );

        SaleChanged::dispatch($sale);

        return $sale;
    }

    private function reserve(Model&AgencyMember $author, SaleOffer $offer, CarbonImmutable $plannedHandoverDate): Sale
    {
        $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($offer->sale->machine_id);
        $lockedSale = Sale::query()->lockForUpdate()->findOrFail($offer->sale_id);
        $lockedOffer = SaleOffer::query()->with('customer')->lockForUpdate()->findOrFail($offer->id);
        $reservedState = $lockedSale->state()->reserve();
        $acceptedState = $lockedOffer->state()->accept();
        $conflictingReservation = $this->handoverConflicts->firstFor($lockedMachine, $plannedHandoverDate);

        if ($conflictingReservation !== null) {
            throw HandoverConflictsWithReservationException::with($conflictingReservation, $plannedHandoverDate);
        }

        $decision = ['decided_by' => $author->getKey(), 'decided_at' => CarbonImmutable::now()];
        $lockedOffer->update(['status' => $acceptedState->status(), ...$decision]);
        SaleOffer::query()->where('sale_id', $lockedSale->id)->where('status', OfferStatus::Pending)->update(['status' => OfferStatus::Rejected, ...$decision]);
        $lockedSale->update([
            'status' => $reservedState->status(),
            'buyer_id' => $lockedOffer->customer_id,
            'accepted_offer_id' => $lockedOffer->id,
            'final_price' => $lockedOffer->amount,
            'planned_handover_date' => $plannedHandoverDate,
        ]);
        $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::OfferAccepted, [
            'amount' => $lockedOffer->amount->format(),
            'customer' => $lockedOffer->customer->name,
            'handover_date' => $plannedHandoverDate->format('d/m/Y'),
        ]);

        return $lockedSale;
    }

    private function translateDatabaseRefusal(Throwable $exception): Throwable
    {
        if ($exception instanceof QueryException && $exception->getCode() === self::UNIQUE_VIOLATION) {
            return OfferRefusedException::concurrentAcceptance();
        }

        return $exception;
    }
}
