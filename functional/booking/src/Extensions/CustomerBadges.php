<?php

namespace Functional\Booking\Extensions;

use Functional\Booking\Contracts\CustomerBadgeProvider;
use Functional\Booking\ValueObjects\CustomerBadge;

final class CustomerBadges
{
    /**
     * @var list<class-string<CustomerBadgeProvider>>
     */
    private array $providerClasses = [];

    /**
     * @param  class-string<CustomerBadgeProvider>  $providerClass
     */
    public function register(string $providerClass): void
    {
        $this->providerClasses[] = $providerClass;
    }

    /**
     * @param  list<int>  $customerIds
     * @return array<int, list<CustomerBadge>>
     */
    public function forCustomers(array $customerIds): array
    {
        $badgesByCustomer = [];

        if ($customerIds === []) {
            return $badgesByCustomer;
        }

        foreach ($this->providers() as $provider) {
            foreach ($provider->badgesFor($customerIds) as $customerId => $badges) {
                $badgesByCustomer[$customerId] = [...$badgesByCustomer[$customerId] ?? [], ...$badges];
            }
        }

        return $badgesByCustomer;
    }

    /**
     * @return list<string>
     */
    public function refreshListeners(): array
    {
        $listeners = [];

        foreach ($this->providers() as $provider) {
            array_push($listeners, ...$provider->refreshListeners());
        }

        return array_values(array_unique($listeners));
    }

    /**
     * @return list<CustomerBadgeProvider>
     */
    private function providers(): array
    {
        return array_map(fn (string $providerClass): CustomerBadgeProvider => app($providerClass), $this->providerClasses);
    }
}
