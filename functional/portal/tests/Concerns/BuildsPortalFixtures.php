<?php

namespace Functional\Portal\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;

trait BuildsPortalFixtures
{
    use CreatesUsers;

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function customerAccount(array $attributes = []): CustomerAccount
    {
        return CustomerAccount::factory()->create($attributes);
    }

    protected function unverifiedCustomerAccount(): CustomerAccount
    {
        return CustomerAccount::factory()->unverified()->create();
    }

    protected function attachedCustomerAccount(?Customer $customer = null): CustomerAccount
    {
        return CustomerAccount::factory()->attachedTo($customer ?? Customer::factory()->create())->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function reservableMachine(?MachineCategory $category = null, ?Agency $agency = null, array $attributes = []): Machine
    {
        return Machine::factory()
            ->for($category ?? MachineCategory::factory(), 'category')
            ->for($agency ?? Agency::factory())
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function pendingRequest(CustomerAccount $account, Machine $machine, string $startDate, string $endDate, array $attributes = []): ReservationRequest
    {
        return ReservationRequest::factory()
            ->for($account, 'account')
            ->for($machine)
            ->create([
                'start_date' => CarbonImmutable::parse($startDate),
                'end_date' => CarbonImmutable::parse($endDate),
                ...$attributes,
            ]);
    }
}
