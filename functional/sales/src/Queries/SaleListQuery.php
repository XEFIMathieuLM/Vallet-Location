<?php

namespace Functional\Sales\Queries;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
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
    public function query(?SaleStatus $status = null, ?MachineCategory $category = null, ?Agency $agency = null, ?CarbonImmutable $periodStart = null, ?CarbonImmutable $periodEnd = null): Builder
    {
        return Sale::query()
            ->with(['machine.category', 'machine.agency', 'buyer'])
            ->when($status, fn (Builder $sales, SaleStatus $status): Builder => $sales->where('status', $status))
            ->when($category, fn (Builder $sales, MachineCategory $category): Builder => $sales->whereHas('machine', fn (Builder $machines): Builder => $machines->whereBelongsTo($category, 'category')))
            ->when($agency, fn (Builder $sales, Agency $agency): Builder => $sales->whereHas('machine', fn (Builder $machines): Builder => $machines->whereBelongsTo($agency)))
            ->when($periodStart !== null && $periodEnd !== null, fn (Builder $sales): Builder => $sales->where(fn (Builder $inPeriod): Builder => $inPeriod
                ->whereBetween('created_at', [$periodStart?->startOfDay(), $periodEnd?->endOfDay()])
                ->orWhereBetween('handed_over_on', [$periodStart?->toDateString(), $periodEnd?->toDateString()])))
            ->latest('id');
    }

    /**
     * @param  Builder<Sale>  $sales
     */
    public function concludedTotal(Builder $sales): Money
    {
        return Money::fromStored((int) $sales->clone()->reorder()->where('status', SaleStatus::Sold)->sum('final_price_cents'));
    }
}
