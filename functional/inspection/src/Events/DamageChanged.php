<?php

namespace Functional\Inspection\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class DamageChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $reservationId,
        public readonly int $unresolvedCount,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fleet');
    }

    public function broadcastAs(): string
    {
        return 'damage.changed';
    }

    /**
     * @return array{reservation_id: int, unresolved_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'unresolved_count' => $this->unresolvedCount,
        ];
    }
}
