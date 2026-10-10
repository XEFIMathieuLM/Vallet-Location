<?php

namespace Functional\Certification\Events;

use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class CertificateChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly ReservationCertificate $certificate) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('fleet');
    }

    public function broadcastAs(): string
    {
        return 'certificate.changed';
    }

    /**
     * @return array{reservation_id: int, status: string, delivered_at: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'reservation_id' => $this->certificate->reservation_id,
            'status' => $this->certificate->status->value,
            'delivered_at' => $this->certificate->delivered_at?->toIso8601String(),
        ];
    }
}
