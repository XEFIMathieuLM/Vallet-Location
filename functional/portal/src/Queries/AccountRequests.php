<?php

namespace Functional\Portal\Queries;

use Functional\Portal\Access\Controls\ReservationRequestControl;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AccountRequests
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, ReservationRequest>
     */
    public function paginate(CustomerAccount $account): LengthAwarePaginator
    {
        return ReservationRequestControl::ownedBy($account)
            ->with(['machine.category', 'machine.agency', 'reservation.machine'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE, pageName: 'demandes');
    }
}
