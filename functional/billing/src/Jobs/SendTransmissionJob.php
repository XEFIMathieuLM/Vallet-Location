<?php

namespace Functional\Billing\Jobs;

use Functional\Billing\Actions\SendTransmission;
use Functional\Billing\Models\Transmission;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendTransmissionJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $transmissionId)
    {
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
