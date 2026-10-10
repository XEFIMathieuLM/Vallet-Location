<?php

namespace Functional\Sales\Guards;

use Carbon\CarbonImmutable;
use Functional\Booking\Contracts\ReservationRequestGuard;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\MachineReservedForSaleException;
use Functional\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

final class ReservedSaleReservationGuard implements ReservationRequestGuard
{
    public function ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        $blockingSale = $this->blockingSales($endDate)->whereBelongsTo($lockedMachine)->first();

        if ($blockingSale !== null) {
            throw MachineReservedForSaleException::until($blockingSale);
        }
    }

    public function excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        $machines->whereNotIn('machines.id', $this->blockingSales($endDate)->select('machine_id'));
    }

    /**
     * @return Builder<Sale>
     */
    private function blockingSales(CarbonImmutable $endDate): Builder
    {
        return Sale::query()
            ->where('status', SaleStatus::Reserved)
            ->whereDate('planned_handover_date', '<=', $endDate);
    }
}
