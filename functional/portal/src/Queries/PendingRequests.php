<?php

namespace Functional\Portal\Queries;

use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PendingRequests
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, ReservationRequest>
     */
    public function paginate(?int $agencyId): LengthAwarePaginator
    {
        return ReservationRequest::query()
            ->with(['account.customer', 'machine.category', 'machine.agency'])
            ->where('status', ReservationRequestStatus::Pending)
            ->when($agencyId, fn (Builder $query, int $agencyId) => $query->whereHas('machine', fn (Builder $machines) => $machines->where('agency_id', $agencyId)))
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }

    public function count(): int
    {
        return ReservationRequest::query()->where('status', ReservationRequestStatus::Pending)->count();
    }
}
