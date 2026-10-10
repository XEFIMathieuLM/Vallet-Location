<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\Transmission;

final class QueueSourceTransmission
{
    public function handle(BillableLineType $type, int $sourceId): Transmission
    {
        $transmission = Transmission::query()->firstOrCreate(['source_type' => $type, 'source_id' => $sourceId]);

        if ($transmission->wasRecentlyCreated) {
            SendTransmissionJob::dispatch($transmission->id);
        }

        return $transmission;
    }
}
