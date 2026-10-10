<?php

namespace Functional\Sales\Guards;

use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Exceptions\MachineHasOpenSaleException;
use Functional\Sales\Models\Sale;

final class OpenSaleRetirementGuard implements MachineRetirementGuard
{
    public function ensureCanRetire(Machine $machine): void
    {
        $openSale = Sale::query()->whereBelongsTo($machine)->whereIn('status', [SaleStatus::Listed, SaleStatus::Reserved])->first();

        if ($openSale !== null) {
            throw MachineHasOpenSaleException::for($machine, $openSale);
        }
    }
}
