<?php

namespace Functional\Accounts\Queries;

use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class MissingPurchaseOrders
{
    /**
     * @return Builder<Reservation>
     */
    public function query(?int $homeAgencyId = null): Builder
    {
        return Reservation::query()
            ->where('status', ReservationStatus::Confirmed)
            ->whereIn('customer_id', KeyAccount::query()->select('customer_id'))
            ->whereNotIn('id', ReservationPurchaseOrder::query()->select('reservation_id'))
            ->when($homeAgencyId !== null, fn (Builder $reservations): Builder => $reservations->whereIn(
                'machine_id',
                Machine::query()->select('id')->where('agency_id', $homeAgencyId),
            ))
            ->with(['machine.agency', 'customer'])
            ->orderBy('start_date')
            ->orderBy('id');
    }
}
