<?php

namespace Functional\Portal\Notifications;

use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Mail\ReservationRequestConfirmedMail;
use Functional\Portal\Mail\ReservationRequestExpiredMail;
use Functional\Portal\Mail\ReservationRequestRefusedMail;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use LogicException;

class ReservationRequestDecided extends Notification
{
    public function __construct(public readonly ReservationRequest $reservationRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CustomerAccount $notifiable): Mailable
    {
        $decisionMail = match ($this->reservationRequest->status) {
            ReservationRequestStatus::Confirmed => new ReservationRequestConfirmedMail($this->reservationRequest),
            ReservationRequestStatus::Refused => new ReservationRequestRefusedMail($this->reservationRequest),
            ReservationRequestStatus::Expired => new ReservationRequestExpiredMail($this->reservationRequest),
            default => throw new LogicException("Reservation request {$this->reservationRequest->id} has no decision to notify."),
        };

        return $decisionMail->to($notifiable->email);
    }
}
