<?php

namespace Functional\Sales\Offers;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\OfferTransition;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class OfferDecision
{
    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function apply(Model&AgencyMember $author, SaleOffer $offer, OfferTransition $transition): SaleOffer
    {
        $decidedOffer = DB::transaction(function () use ($author, $offer, $transition): SaleOffer {
            $lockedOffer = SaleOffer::query()->with(['sale', 'customer'])->lockForUpdate()->findOrFail($offer->id);
            $nextState = match ($transition) {
                OfferTransition::Accept => $lockedOffer->state()->accept(),
                OfferTransition::Reject => $lockedOffer->state()->reject(),
                OfferTransition::Withdraw => $lockedOffer->state()->withdraw(),
            };

            $lockedOffer->update(['status' => $nextState->status(), 'decided_by' => $author->getKey(), 'decided_at' => CarbonImmutable::now()]);
            $this->saleHistory->record($lockedOffer->sale, $author, $this->historyEvent($transition), [
                'amount' => $lockedOffer->amount->format(),
                'customer' => $lockedOffer->customer->name,
            ]);

            return $lockedOffer;
        });

        SaleChanged::dispatch($decidedOffer->sale);

        return $decidedOffer;
    }

    private function historyEvent(OfferTransition $transition): SaleHistoryEvent
    {
        return match ($transition) {
            OfferTransition::Accept => SaleHistoryEvent::OfferAccepted,
            OfferTransition::Reject => SaleHistoryEvent::OfferRejected,
            OfferTransition::Withdraw => SaleHistoryEvent::OfferWithdrawn,
        };
    }
}
