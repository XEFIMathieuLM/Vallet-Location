<?php

namespace Functional\Fleet\Queries;

use Carbon\CarbonImmutable;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class VgpWatchList
{
    /**
     * @return Builder<Machine>
     */
    public function query(?int $agencyId, CarbonImmutable $until): Builder
    {
        return Machine::query()
            ->with('category')
            ->where('is_subject_to_vgp', true)
            ->where('status', '<>', MachineStatus::Retired)
            ->where(fn (Builder $dueMachines): Builder => $dueMachines
                ->whereNull('vgp_due_date')
                ->orWhereDate('vgp_due_date', '<=', $until))
            ->when($agencyId !== null, fn (Builder $machines): Builder => $machines->where('agency_id', $agencyId))
            ->orderByRaw('vgp_due_date asc nulls first')
            ->orderBy('reference');
    }
}
