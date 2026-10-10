<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\ReasonRequiredException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ReleaseSaleReservation
{
    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function handle(Model&AgencyMember $author, Sale $sale, string $reason): Sale
    {
        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw ReasonRequiredException::missing();
        }

        $releasedSale = DB::transaction(function () use ($author, $sale, $trimmedReason): Sale {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $listedState = $lockedSale->state()->release();
            $acceptedOffer = SaleOffer::query()->lockForUpdate()->findOrFail($lockedSale->accepted_offer_id);

            $lockedSale->update([
                'status' => $listedState->status(),
                'buyer_id' => null,
                'accepted_offer_id' => null,
                'final_price' => null,
                'planned_handover_date' => null,
            ]);
            $acceptedOffer->update(['status' => $acceptedOffer->state()->withdraw()->status(), 'decided_by' => $author->getKey(), 'decided_at' => CarbonImmutable::now()]);
            $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::ReservationReleased, ['reason' => $trimmedReason]);

            return $lockedSale;
        });

        SaleChanged::dispatch($releasedSale);

        return $releasedSale;
    }
}
