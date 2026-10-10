<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Support\TransmissionLifecycle;
use Illuminate\Support\Facades\DB;

final class RetryTransmission
{
    public function __construct(private readonly TransmissionLifecycle $transmissionLifecycle) {}

    public function handle(Transmission $transmission): void
    {
        DB::transaction(function () use ($transmission): void {
            $lockedTransmission = Transmission::query()->lockForUpdate()->findOrFail($transmission->id);
            $this->transmissionLifecycle->requeue($lockedTransmission);

            SendTransmissionJob::dispatch($lockedTransmission->id);
        });
    }
}
