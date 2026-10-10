<?php

namespace Functional\Deposit\Actions;

use Functional\Billing\Money\Money;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SetDepositRate
{
    public function handle(?MachineCategory $category, Money $amount, Model&AgencyMember $author): DepositRate
    {
        if (! $amount->isPositive()) {
            throw DepositRefusedException::invalidAmount();
        }

        return DB::transaction(fn (): DepositRate => DepositRate::query()->updateOrCreate(
            ['machine_category_id' => $category?->id],
            ['amount' => $amount, 'updated_by' => $author->getKey()],
        ));
    }
}
