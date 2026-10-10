<?php

namespace Functional\Portal\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AccountReservations
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Reservation>|null
     */
    public function paginate(CustomerAccount $account): ?LengthAwarePaginator
    {
        if (! $account->isAttached()) {
            return null;
        }

        $today = CarbonImmutable::today()->toDateString();

        return Reservation::query()
            ->where('customer_id', $account->customer_id)
            ->with(['machine.category', 'machine.agency'])
            ->orderByRaw('case when end_date >= ? then 0 else 1 end', [$today])
            ->orderByRaw('case when end_date >= ? then start_date end asc', [$today])
            ->orderByDesc('start_date')
            ->paginate(self::PER_PAGE, pageName: 'reservations');
    }
}
