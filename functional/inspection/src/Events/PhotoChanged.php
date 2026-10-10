<?php

namespace Functional\Inspection\Events;

use Functional\Inspection\Enums\InspectionStep;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PhotoChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $reservationId,
        public readonly InspectionStep $step,
        public readonly int $reservationViewId,
        public readonly int $missingViewsCount,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("reservation.{$this->reservationId}");
    }

    public function broadcastAs(): string
    {
        return 'photo.changed';
    }

    /**
     * @return array{reservation_id: int, step: string, reservation_view_id: int, missing_views_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'step' => $this->step->value,
            'reservation_view_id' => $this->reservationViewId,
            'missing_views_count' => $this->missingViewsCount,
        ];
    }
}
