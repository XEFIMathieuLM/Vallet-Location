<?php

namespace Functional\Inspection\Events;

use Functional\Inspection\Enums\InspectionStep;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PhotoSessionChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $reservationId,
        public readonly InspectionStep $step,
        public readonly bool $isActive,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("reservation.{$this->reservationId}");
    }

    public function broadcastAs(): string
    {
        return 'photo-session.changed';
    }

    /**
     * @return array{reservation_id: int, step: string, is_active: bool}
     */
    public function broadcastWith(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'step' => $this->step->value,
            'is_active' => $this->isActive,
        ];
    }
}
