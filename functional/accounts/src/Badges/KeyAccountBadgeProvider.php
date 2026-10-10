<?php

namespace Functional\Accounts\Badges;

use Functional\Accounts\Support\KeyAccounts;
use Functional\Booking\Contracts\CustomerBadgeProvider;
use Functional\Booking\ValueObjects\CustomerBadge;

final class KeyAccountBadgeProvider implements CustomerBadgeProvider
{
    public function __construct(private readonly KeyAccounts $keyAccounts) {}

    public function badgesFor(array $customerIds): array
    {
        $badge = new CustomerBadge(
            label: __('accounts::key_accounts.badge.label'),
            color: 'violet',
            url: route('accounts.key-accounts'),
            description: __('accounts::key_accounts.badge.description'),
        );

        return array_fill_keys($this->keyAccounts->keyAccountIdsAmong($customerIds), [$badge]);
    }

    public function refreshListeners(): array
    {
        return [];
    }
}
