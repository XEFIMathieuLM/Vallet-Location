<?php

namespace Functional\Certification\Queries;

use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Collection;

final class ReportInForce
{
    public function for(Machine $machine): ?VgpReport
    {
        return VgpReport::query()->where('machine_id', $machine->id)->latest('id')->first();
    }

    /**
     * @param  iterable<int>  $machineIds
     * @return Collection<int, VgpReport>
     */
    public function forMachines(iterable $machineIds): Collection
    {
        return VgpReport::query()
            ->whereIn('id', VgpReport::query()->selectRaw('max(id)')->whereIn('machine_id', collect($machineIds)->all())->groupBy('machine_id'))
            ->get()
            ->keyBy('machine_id');
    }
}
