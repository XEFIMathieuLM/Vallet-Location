<?php

namespace Functional\Deposit\Access\Controls;

use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class DepositRateControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = DepositRate::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(DepositPermission::ManageDepositRates->value))
                ->should(fn (Model $user, Model $depositRate): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
