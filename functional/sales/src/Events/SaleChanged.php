<?php

namespace Functional\Sales\Events;

use Functional\Sales\Models\Sale;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SaleChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Sale $sale) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('sales')];
    }

    public function broadcastAs(): string
    {
        return 'sale.changed';
    }

    /**
     * @return array{id: int, machine_id: int, status: string, planned_handover_date: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->sale->id,
            'machine_id' => $this->sale->machine_id,
            'status' => $this->sale->status->value,
            'planned_handover_date' => $this->sale->planned_handover_date?->toDateString(),
        ];
    }
}
