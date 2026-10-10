<?php

namespace Functional\Sales\Queries;

use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

final class SaleListQuery
{
    /**
     * @return Builder<Sale>
     */
    public function query(?SaleStatus $status = null, ?MachineCategory $category = null, ?Agency $agency = null): Builder
    {
        return Sale::query()
            ->with(['machine.category', 'machine.agency', 'buyer'])
            ->when($status, fn (Builder $sales, SaleStatus $status): Builder => $sales->where('status', $status))
            ->when($category, fn (Builder $sales, MachineCategory $category): Builder => $sales->whereHas('machine', fn (Builder $machines): Builder => $machines->whereBelongsTo($category, 'category')))
            ->when($agency, fn (Builder $sales, Agency $agency): Builder => $sales->whereHas('machine', fn (Builder $machines): Builder => $machines->whereBelongsTo($agency)))
            ->latest('id');
    }
}
