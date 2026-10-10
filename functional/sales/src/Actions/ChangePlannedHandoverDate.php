<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Enums\SaleTransition;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\HandoverConflictsWithReservationException;
use Functional\Sales\Exceptions\IllegalSaleTransitionException;
use Functional\Sales\Exceptions\InvalidHandoverDateException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Functional\Sales\Queries\HandoverConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ChangePlannedHandoverDate
{
    public function __construct(
        private readonly HandoverConflicts $handoverConflicts,
        private readonly SaleHistory $saleHistory,
    ) {}

    public function handle(Model&AgencyMember $author, Sale $sale, CarbonImmutable $plannedHandoverDate): Sale
    {
        if ($plannedHandoverDate->startOfDay()->lt(CarbonImmutable::today())) {
            throw InvalidHandoverDateException::inThePast($plannedHandoverDate);
        }

        $updatedSale = DB::transaction(function () use ($author, $sale, $plannedHandoverDate): Sale {
            $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($sale->machine_id);
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($lockedSale->status !== SaleStatus::Reserved) {
                throw IllegalSaleTransitionException::for($lockedSale->state(), SaleTransition::Reserve);
            }

            $conflictingReservation = $this->handoverConflicts->firstFor($lockedMachine, $plannedHandoverDate);

            if ($conflictingReservation !== null) {
                throw HandoverConflictsWithReservationException::with($conflictingReservation, $plannedHandoverDate);
            }

            $previousDate = $lockedSale->planned_handover_date;
            $lockedSale->update(['planned_handover_date' => $plannedHandoverDate]);
            $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::HandoverDateChanged, [
                'old_date' => $previousDate?->format('d/m/Y'),
                'new_date' => $plannedHandoverDate->format('d/m/Y'),
            ]);

            return $lockedSale;
        });

        SaleChanged::dispatch($updatedSale);

        return $updatedSale;
    }
}
