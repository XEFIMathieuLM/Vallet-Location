<?php

namespace Functional\Sales\Billing;

use Functional\Billing\Contracts\BillableSource;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Transmissions\TransmissionSubject;
use Functional\Sales\Exceptions\UnsoldSaleTransmissionException;
use Functional\Sales\Models\Sale;
use Illuminate\Support\Collection;

final class SaleBillableSource implements BillableSource
{
    private const RELATIONS = ['machine.category', 'machine.agency', 'agency', 'buyer'];

    public function type(): BillableLineType
    {
        return BillableLineType::UsedMachineSale;
    }

    public function line(Transmission $transmission): BillableLine
    {
        $sale = Sale::query()->with(self::RELATIONS)->findOrFail($transmission->source_id);

        return new SaleLine(
            idempotencyKey: $transmission->uuid,
            customerRef: CustomerBillingAccount::query()->where('customer_id', $sale->buyer_id)->value('external_ref'),
            saleRef: "SALE-{$sale->id}",
            machineReference: $sale->machine->reference,
            machineCategory: $sale->machine->category->name,
            homeAgency: $sale->machine->agency->name,
            sellingAgency: $sale->agency->name,
            saleDate: (string) $sale->handed_over_on?->toDateString(),
            label: __('sales::sales.billing.label', ['reference' => $sale->machine->reference]),
            price: $sale->final_price ?? throw UnsoldSaleTransmissionException::for($sale),
        );
    }

    public function subjects(Collection $transmissions): array
    {
        $sales = Sale::query()->with(['machine', 'buyer'])->findMany($transmissions->pluck('source_id'))->keyBy('id');

        return $transmissions->mapWithKeys(function (Transmission $transmission) use ($sales): array {
            $sale = $sales[$transmission->source_id];

            return [$transmission->id => new TransmissionSubject(
                label: __('sales::sales.billing.subject', ['reference' => $sale->machine->reference]),
                customerId: (int) $sale->buyer_id,
                customerName: (string) $sale->buyer?->name,
                url: route('sales.show', $sale),
                historySubject: $sale,
            )];
        })->all();
    }
}
