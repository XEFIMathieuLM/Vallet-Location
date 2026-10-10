<?php

namespace Functional\Certification\Listeners;

use Functional\Booking\Events\ReservationChanged;
use Functional\Certification\Actions\OpenReservationCertificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class OpenCertificateOnReservationChanged implements ShouldQueue, ShouldQueueAfterCommit
{
    public function __construct(private readonly OpenReservationCertificate $openReservationCertificate) {}

    public function handle(ReservationChanged $reservationChanged): void
    {
        $this->openReservationCertificate->handle($reservationChanged->reservation);
    }
}
