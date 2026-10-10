<?php

namespace Functional\Portal\Mail;

use Functional\Portal\Models\ReservationRequest;

final class ReservationRequestMailDetails
{
    /**
     * @return array{accountName: string, machineReference: string, machineCategory: string, startDate: string, endDate: string, agencyName: string, agencyAddress: string|null, refusalReason: string|null}
     */
    public static function of(ReservationRequest $reservationRequest): array
    {
        $machine = $reservationRequest->reservation->machine ?? $reservationRequest->machine;

        return [
            'accountName' => $reservationRequest->account->name,
            'machineReference' => $machine->reference,
            'machineCategory' => $machine->category->name,
            'startDate' => $reservationRequest->start_date->format('d/m/Y'),
            'endDate' => $reservationRequest->end_date->format('d/m/Y'),
            'agencyName' => $machine->agency->name,
            'agencyAddress' => $machine->agency->address,
            'refusalReason' => $reservationRequest->refusal_reason,
        ];
    }
}
