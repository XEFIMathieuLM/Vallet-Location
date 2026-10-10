<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\QueueSourceTransmission;
use Functional\Billing\Enums\BillableLineType;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\SaleHandoverRefusedException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class HandOverSale
{
    public function __construct(
        private readonly RetireMachine $retireMachine,
        private readonly QueueSourceTransmission $queueSourceTransmission,
        private readonly SaleHistory $saleHistory,
    ) {}

    public function handle(Model&AgencyMember $author, Sale $sale): Sale
    {
        $soldSale = DB::transaction(function () use ($author, $sale): Sale {
            $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($sale->machine_id);
            $lockedSale = Sale::query()->with('buyer')->lockForUpdate()->findOrFail($sale->id);
            $soldState = $lockedSale->state()->sell();

            $this->ensureMachineIsFree($lockedMachine);

            $lockedSale->update(['status' => $soldState->status(), 'handed_over_on' => CarbonImmutable::today(), 'handed_over_by' => $author->getKey()]);

            if ($lockedMachine->status !== MachineStatus::Retired) {
                $this->retireMachine->handle($lockedMachine);
            }

            $this->queueSourceTransmission->handle(BillableLineType::UsedMachineSale, $lockedSale->id);
            $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::HandedOver, [
                'customer' => $lockedSale->buyer?->name,
                'price' => $lockedSale->final_price?->format(),
            ]);

            return $lockedSale;
        });

        SaleChanged::dispatch($soldSale);

        return $soldSale;
    }

    private function ensureMachineIsFree(Machine $machine): void
    {
        $activeReservations = Reservation::query()
            ->with('agency')
            ->whereBelongsTo($machine)
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::InProgress])
            ->orderBy('start_date')
            ->get();
        $rentalInProgress = $activeReservations->firstWhere('status', ReservationStatus::InProgress);

        if ($rentalInProgress instanceof Reservation) {
            throw SaleHandoverRefusedException::machineRentedOut($rentalInProgress);
        }

        if ($activeReservations->isNotEmpty()) {
            throw SaleHandoverRefusedException::activeReservations($activeReservations->toBase());
        }
    }
}
