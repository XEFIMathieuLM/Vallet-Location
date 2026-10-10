<?php

namespace Functional\Portal\Events;

use Functional\Portal\Models\ReservationRequest;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReservationRequestChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ReservationRequest $reservationRequest) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('portal-requests')];
    }

    public function broadcastAs(): string
    {
        return 'reservation-request.changed';
    }

    /**
     * @return array{id: int, status: string, machine_id: int, start_date: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->reservationRequest->id,
            'status' => $this->reservationRequest->status->value,
            'machine_id' => $this->reservationRequest->machine_id,
            'start_date' => $this->reservationRequest->start_date->toDateString(),
        ];
    }
}
