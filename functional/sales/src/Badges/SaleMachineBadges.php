<?php

namespace Functional\Sales\Badges;

use Functional\Fleet\Contracts\MachineBadgeProvider;
use Functional\Fleet\Data\MachineBadge;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;

final class SaleMachineBadges implements MachineBadgeProvider
{
    public function badgesFor(array $machineIds): array
    {
        return Sale::query()
            ->whereIn('machine_id', $machineIds)
            ->whereIn('status', [SaleStatus::Listed, SaleStatus::Reserved])
            ->get()
            ->mapWithKeys(fn (Sale $sale): array => [$sale->machine_id => [$this->badge($sale)]])
            ->all();
    }

    public function refreshListeners(): array
    {
        return ['echo-private:sales,.sale.changed'];
    }

    private function badge(Sale $sale): MachineBadge
    {
        $label = $sale->status === SaleStatus::Reserved
            ? __('sales::sales.badges.reserved', ['date' => $sale->planned_handover_date?->format('d/m')])
            : __('sales::sales.badges.listed');

        return new MachineBadge($label, $sale->status->color(), route('sales.show', $sale));
    }
}
