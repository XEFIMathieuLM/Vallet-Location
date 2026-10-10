<?php

namespace Functional\Booking\Events;

use Functional\Booking\Models\Reservation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReservationChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('fleet')];
    }

    public function broadcastAs(): string
    {
        return 'reservation.changed';
    }

    /**
     * @return array{id: int, machine_id: int, status: string, start_date: string, end_date: string, conflict_reason: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->reservation->id,
            'machine_id' => $this->reservation->machine_id,
            'status' => $this->reservation->status->value,
            'start_date' => $this->reservation->start_date->toDateString(),
            'end_date' => $this->reservation->end_date->toDateString(),
            'conflict_reason' => $this->reservation->conflict_reason?->value,
        ];
    }
}
