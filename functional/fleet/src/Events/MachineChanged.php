<?php

namespace Functional\Fleet\Events;

use Functional\Fleet\Models\Machine;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MachineChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Machine $machine) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('fleet')];
    }

    public function broadcastAs(): string
    {
        return 'machine.changed';
    }

    /**
     * @return array{id: int, status: string, agency_id: int, vgp_due_date: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->machine->id,
            'status' => $this->machine->status->value,
            'agency_id' => $this->machine->agency_id,
            'vgp_due_date' => $this->machine->vgp_due_date?->toDateString(),
        ];
    }
}
