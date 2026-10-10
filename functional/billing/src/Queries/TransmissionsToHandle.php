<?php

namespace Functional\Billing\Queries;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\Transmission;
use Illuminate\Database\Eloquent\Builder;

final class TransmissionsToHandle
{
    /**
     * @return Builder<Transmission>
     */
    public function query(): Builder
    {
        $pendingSince = CarbonImmutable::now()->subHours(config()->integer('billing.alert_after_hours'));

        return Transmission::query()->where(fn (Builder $transmissions): Builder => $transmissions
            ->where('status', TransmissionStatus::Failed)
            ->orWhere(fn (Builder $pendingTransmissions): Builder => $pendingTransmissions
                ->where('status', TransmissionStatus::Pending)
                ->where('created_at', '<=', $pendingSince)));
    }
}
