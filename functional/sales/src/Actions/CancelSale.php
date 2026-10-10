<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\ReasonRequiredException;
use Functional\Sales\Exceptions\SaleCancellationRefusedException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CancelSale
{
    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function handle(Model&AgencyMember $author, Sale $sale, string $reason): Sale
    {
        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw ReasonRequiredException::missing();
        }

        $cancelledSale = DB::transaction(function () use ($author, $sale, $trimmedReason): Sale {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($lockedSale->status === SaleStatus::Sold) {
                throw SaleCancellationRefusedException::sold($lockedSale);
            }

            $cancelledState = $lockedSale->state()->cancel();
            $decision = ['decided_by' => $author->getKey(), 'decided_at' => CarbonImmutable::now()];
            SaleOffer::query()->where('sale_id', $lockedSale->id)->where('status', OfferStatus::Pending)->update(['status' => OfferStatus::Rejected, ...$decision]);
            SaleOffer::query()->where('sale_id', $lockedSale->id)->where('status', OfferStatus::Accepted)->update(['status' => OfferStatus::Withdrawn, ...$decision]);
            $lockedSale->update([
                'status' => $cancelledState->status(),
                'buyer_id' => null,
                'accepted_offer_id' => null,
                'final_price' => null,
                'planned_handover_date' => null,
                'cancellation_reason' => $trimmedReason,
                'cancelled_by' => $author->getKey(),
            ]);
            $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::Cancelled, ['reason' => $trimmedReason]);

            return $lockedSale;
        });

        SaleChanged::dispatch($cancelledSale);

        return $cancelledSale;
    }
}
