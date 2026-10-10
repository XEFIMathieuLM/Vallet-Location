<?php

namespace Functional\Booking\Contracts;

use Functional\Booking\ValueObjects\CustomerBadge;

interface CustomerBadgeProvider
{
    /**
     * @param  list<int>  $customerIds
     * @return array<int, list<CustomerBadge>>
     */
    public function badgesFor(array $customerIds): array;

    /**
     * @return list<string>
     */
    public function refreshListeners(): array;
}
