<?php

namespace Functional\Billing\Access\Controls;

use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Models\DamageSettlement;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class DamageSettlementControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = DamageSettlement::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(BillingPermission::Manage->value))
                ->should(fn (Model $user, Model $damageSettlement): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
