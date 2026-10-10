<?php

namespace Functional\Fleet\Queries;

use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class FleetStatusCounts
{
    /**
     * @return array<string, int>
     */
    public function count(?int $agencyId): array
    {
        $countsByStatus = Machine::query()
            ->select('status')
            ->selectRaw('count(*) as machines_count')
            ->where('status', '<>', MachineStatus::Retired)
            ->when($agencyId !== null, fn (Builder $machines): Builder => $machines->where('agency_id', $agencyId))
            ->groupBy('status')
            ->toBase()
            ->pluck('machines_count', 'status');

        return collect(MachineStatus::cases())
            ->reject(fn (MachineStatus $status): bool => $status === MachineStatus::Retired)
            ->mapWithKeys(fn (MachineStatus $status): array => [$status->value => (int) $countsByStatus->get($status->value, 0)])
            ->all();
    }
}
