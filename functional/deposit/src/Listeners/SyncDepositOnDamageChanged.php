<?php

namespace Functional\Deposit\Listeners;

use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\SyncDepositStatus;
use Functional\Inspection\Events\DamageChanged;

final class SyncDepositOnDamageChanged
{
    public function __construct(private readonly SyncDepositStatus $syncDepositStatus) {}

    public function handle(DamageChanged $event): void
    {
        $reservation = Reservation::query()->find($event->reservationId);

        if ($reservation !== null) {
            $this->syncDepositStatus->for($reservation);
        }
    }
}
