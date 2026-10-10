<?php

namespace Functional\Sales\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\InvalidSaleListingException;
use Functional\Sales\Exceptions\SaleListingLockedException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class UpdateSaleListing
{
    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function handle(Model&AgencyMember $author, Sale $sale, SaleListing $listing): Sale
    {
        if (! $listing->askingPrice->isPositive()) {
            throw InvalidSaleListingException::nonPositivePrice();
        }

        $updatedSale = DB::transaction(function () use ($author, $sale, $listing): Sale {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($lockedSale->status === SaleStatus::Sold) {
                throw SaleListingLockedException::sold($lockedSale);
            }

            $this->changeAskingPrice($author, $lockedSale, $listing);
            $this->changeDescription($author, $lockedSale, $listing);

            return $lockedSale;
        });

        SaleChanged::dispatch($updatedSale);

        return $updatedSale;
    }

    private function changeAskingPrice(Model&AgencyMember $author, Sale $sale, SaleListing $listing): void
    {
        if ($sale->asking_price->equals($listing->askingPrice)) {
            return;
        }

        if (! $sale->state()->acceptsAskingPriceChange()) {
            throw SaleListingLockedException::askingPrice($sale);
        }

        $oldPrice = $sale->asking_price;
        $sale->update(['asking_price' => $listing->askingPrice]);
        $this->saleHistory->record($sale, $author, SaleHistoryEvent::AskingPriceChanged, [
            'old_price' => $oldPrice->format(),
            'new_price' => $listing->askingPrice->format(),
        ]);
    }

    private function changeDescription(Model&AgencyMember $author, Sale $sale, SaleListing $listing): void
    {
        $sale->fill($listing->description());

        if (! $sale->isDirty()) {
            return;
        }

        if (! $sale->state()->acceptsDescriptionChange()) {
            throw SaleListingLockedException::description($sale);
        }

        $sale->save();
        $this->saleHistory->record($sale, $author, SaleHistoryEvent::DescriptionChanged);
    }
}
