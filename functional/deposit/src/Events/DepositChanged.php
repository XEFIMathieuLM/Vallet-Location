<?php

namespace Functional\Deposit\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class DepositChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int $reservationId) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('fleet')];
    }

    public function broadcastAs(): string
    {
        return 'deposit.changed';
    }

    /**
     * @return array{reservation_id: int}
     */
    public function broadcastWith(): array
    {
        return ['reservation_id' => $this->reservationId];
    }
}
