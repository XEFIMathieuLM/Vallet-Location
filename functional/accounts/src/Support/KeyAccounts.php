<?php

namespace Functional\Accounts\Support;

use Functional\Accounts\Models\KeyAccount;

final class KeyAccounts
{
    public function isKeyAccount(int $customerId): bool
    {
        return KeyAccount::query()->where('customer_id', $customerId)->exists();
    }

    /**
     * @param  list<int>  $customerIds
     * @return list<int>
     */
    public function keyAccountIdsAmong(array $customerIds): array
    {
        if ($customerIds === []) {
            return [];
        }

        return array_values(KeyAccount::query()->whereIn('customer_id', $customerIds)->pluck('customer_id')->map(fn (int $customerId): int => $customerId)->all());
    }
}
