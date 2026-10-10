<?php

namespace Functional\Booking\Actions;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class UpdateCustomer
{
    public function __construct(private readonly CustomerChangeGuards $changeGuards) {}

    public function qualify(Customer $customer, CustomerType $type, Authenticatable&AgencyMember $author): Customer
    {
        $isChanged = DB::transaction(function () use ($customer, $type): bool {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if ($lockedCustomer->type === $type) {
                return false;
            }

            foreach ($this->changeGuards->all() as $changeGuard) {
                $changeGuard->beforeTypeChange($lockedCustomer, $type);
            }

            $lockedCustomer->update(['type' => $type]);

            return true;
        });

        $customer->refresh();

        if ($isChanged) {
            CustomerChanged::dispatch($customer, ['type']);
        }

        return $customer;
    }
}
