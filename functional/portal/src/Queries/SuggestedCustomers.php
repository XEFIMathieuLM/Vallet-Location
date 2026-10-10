<?php

namespace Functional\Portal\Queries;

use Functional\Booking\Models\Customer;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Database\Eloquent\Collection;

final class SuggestedCustomers
{
    private const LIMIT = 10;

    private const MIN_SEARCH_LENGTH = 2;

    /**
     * @return Collection<int, Customer>
     */
    public function for(CustomerAccount $account): Collection
    {
        return Customer::query()
            ->where(fn ($matching) => $matching->whereRaw('lower(email) = ?', [$account->email])->orWhere('phone', $account->phone))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    public function search(string $searchTerm): Collection
    {
        $trimmedTerm = trim($searchTerm);

        if (mb_strlen($trimmedTerm) < self::MIN_SEARCH_LENGTH) {
            return new Collection;
        }

        $pattern = '%'.mb_strtolower($trimmedTerm).'%';

        return Customer::query()
            ->where(fn ($matching) => $matching->whereRaw('lower(name) like ?', [$pattern])->orWhereRaw('lower(email) like ?', [$pattern])->orWhere('phone', 'like', $pattern))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }
}
