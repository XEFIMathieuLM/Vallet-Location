<?php

namespace Functional\Deposit\Actions;

use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Models\MachineCategory;

final class RemoveDepositRate
{
    public function handle(MachineCategory $category): void
    {
        DepositRate::query()->where('machine_category_id', $category->id)->delete();
    }
}
