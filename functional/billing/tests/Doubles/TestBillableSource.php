<?php

namespace Functional\Billing\Tests\Doubles;

use Functional\Billing\Contracts\BillableSource;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Transmissions\TransmissionSubject;
use Functional\Booking\Models\Customer;
use Illuminate\Support\Collection;

final class TestBillableSource implements BillableSource
{
    public function type(): BillableLineType
    {
        return BillableLineType::UsedMachineSale;
    }

    public function line(Transmission $transmission): BillableLine
    {
        return new TestSourceLine(
            $transmission->uuid,
            CustomerBillingAccount::query()->where('customer_id', $transmission->source_id)->value('external_ref'),
            "TEST-{$transmission->source_id}",
            Money::fromStored(1650000),
        );
    }

    public function subjects(Collection $transmissions): array
    {
        $customers = Customer::query()->findMany($transmissions->pluck('source_id'))->keyBy('id');

        return $transmissions->mapWithKeys(fn (Transmission $transmission): array => [
            $transmission->id => new TransmissionSubject(
                label: "Source de test {$transmission->source_id}",
                customerId: (int) $transmission->source_id,
                customerName: $customers[$transmission->source_id]->name,
                url: "https://example.test/sources/{$transmission->source_id}",
                historySubject: $customers[$transmission->source_id],
            ),
        ])->all();
    }
}
