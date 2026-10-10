<?php

namespace Functional\Sales\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Enums\SaleHistoryEvent;
use Functional\Sales\Events\SaleChanged;
use Functional\Sales\Exceptions\OfferRefusedException;
use Functional\Sales\History\SaleHistory;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class RecordOffer
{
    public function __construct(private readonly SaleHistory $saleHistory) {}

    public function handle(Model&AgencyMember $author, Sale $sale, Customer|NewCustomer $buyer, Money $amount, CarbonImmutable $offeredOn): SaleOffer
    {
        if (! $amount->isPositive()) {
            throw OfferRefusedException::nonPositiveAmount();
        }

        if ($offeredOn->startOfDay()->gt(CarbonImmutable::today())) {
            throw OfferRefusedException::offeredInTheFuture();
        }

        $offer = DB::transaction(function () use ($author, $sale, $buyer, $amount, $offeredOn): SaleOffer {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $lockedSale->state()->acceptsOffers()) {
                throw OfferRefusedException::saleNotListed($lockedSale);
            }

            $customer = $this->persistedCustomer($buyer);
            $offer = SaleOffer::query()->create([
                'sale_id' => $lockedSale->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'offered_on' => $offeredOn,
                'status' => OfferStatus::Pending,
                'recorded_by' => $author->getKey(),
            ]);
            $this->saleHistory->record($lockedSale, $author, SaleHistoryEvent::OfferRecorded, ['amount' => $amount->format(), 'customer' => $customer->name]);

            return $offer;
        });

        SaleChanged::dispatch($sale);

        return $offer;
    }

    private function persistedCustomer(Customer|NewCustomer $buyer): Customer
    {
        if ($buyer instanceof Customer) {
            return $buyer;
        }

        return Customer::query()->create(['name' => $buyer->name, 'phone' => $buyer->phone, 'email' => $buyer->email]);
    }
}
