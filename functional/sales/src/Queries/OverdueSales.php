<?php

namespace Functional\Sales\Queries;

use Carbon\CarbonImmutable;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

final class OverdueSales
{
    /**
     * @return Builder<Sale>
     */
    public function query(CarbonImmutable $today): Builder
    {
        return Sale::query()
            ->where('status', SaleStatus::Reserved)
            ->whereDate('planned_handover_date', '<', $today);
    }
}
