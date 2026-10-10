<?php

namespace Functional\Billing\Jobs;

use Functional\Billing\Actions\SendTransmission;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Transmissions\TransmissionReservation;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendTransmissionJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout;

    public function __construct(public readonly int $transmissionId)
    {
        $this->timeout = app(TransmissionReservation::class)->seconds();
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->transmissionId;
    }

    public function handle(SendTransmission $sendTransmission): void
    {
        $sendTransmission->handle(Transmission::query()->findOrFail($this->transmissionId));
    }
}
