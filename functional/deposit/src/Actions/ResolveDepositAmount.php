<?php

namespace Functional\Deposit\Actions;

use Functional\Billing\Money\Money;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Models\MachineCategory;

final class ResolveDepositAmount
{
    public function forCategory(MachineCategory $category): Money
    {
        return $this->forCategoryId($category->id);
    }

    public function forReservation(Reservation $reservation): Money
    {
        return $this->forCategoryId($reservation->machine()->value('machine_category_id'));
    }

    private function forCategoryId(?int $categoryId): Money
    {
        $applicableRate = DepositRate::query()
            ->where(fn ($query) => $query->where('machine_category_id', $categoryId)->orWhereNull('machine_category_id'))
            ->orderByRaw('machine_category_id IS NULL')
            ->firstOrFail();

        return $applicableRate->amount;
    }
}
