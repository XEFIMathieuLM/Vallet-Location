<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Booking\Contracts\CustomerBadgeProvider;
use Functional\Booking\ValueObjects\CustomerBadge;

final class TestCustomerBadgeProvider implements CustomerBadgeProvider
{
    public function badgesFor(array $customerIds): array
    {
        return array_fill_keys($customerIds, [new CustomerBadge('Badge de test', 'zinc', description: 'Précision du badge')]);
    }

    public function refreshListeners(): array
    {
        return ['echo-private:test,.changed'];
    }
}
