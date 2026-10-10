<?php

namespace Functional\Portal\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Data\PortalMachineOffer;
use Functional\Portal\Models\CategoryIndicativePrice;

final class PortalMachineSearch
{
    public function __construct(private readonly AvailableMachinesQuery $availableMachinesQuery) {}

    /**
     * @return list<PortalMachineOffer>
     */
    public function offers(CarbonImmutable $startDate, CarbonImmutable $endDate, MachineCategory $category, Agency $agency): array
    {
        $machines = $this->availableMachinesQuery->get($startDate, $endDate, $category, $agency);
        $dailyPriceCents = $this->dailyPricesOf($machines->pluck('machine_category_id')->unique()->all());

        return array_values($machines->map(fn (Machine $machine): PortalMachineOffer => new PortalMachineOffer(
            $machine->id,
            $machine->reference,
            $machine->category->name,
            $machine->agency->name,
            $dailyPriceCents[$machine->machine_category_id] ?? null,
        ))->all());
    }

    public function isOffered(Machine $machine, CarbonImmutable $startDate, CarbonImmutable $endDate): bool
    {
        return $this->availableMachinesQuery
            ->get($startDate, $endDate, $machine->category, $machine->agency)
            ->contains('id', $machine->id);
    }

    public function dailyPriceCentsOf(MachineCategory $category): ?int
    {
        return $this->dailyPricesOf([$category->id])[$category->id] ?? null;
    }

    /**
     * @param  array<int, int>  $categoryIds
     * @return array<int, int>
     */
    private function dailyPricesOf(array $categoryIds): array
    {
        return CategoryIndicativePrice::query()
            ->whereIn('machine_category_id', $categoryIds)
            ->pluck('daily_price_cents', 'machine_category_id')
            ->all();
    }
}
