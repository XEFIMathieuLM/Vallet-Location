<?php

namespace Functional\Deposit\Listeners;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Deposit\Actions\SyncDepositStatus;

final class SyncDepositOnReservationChanged
{
    public function __construct(private readonly SyncDepositStatus $syncDepositStatus) {}

    public function handle(ReservationChanged $event): void
    {
        if (in_array($event->reservation->status, [ReservationStatus::Closed, ReservationStatus::Cancelled], true)) {
            $this->syncDepositStatus->for($event->reservation);
        }
    }
}
