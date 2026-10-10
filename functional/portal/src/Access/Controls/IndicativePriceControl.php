<?php

namespace Functional\Portal\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class IndicativePriceControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = CategoryIndicativePrice::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && ! $user instanceof CustomerAccount && $user->can(PortalPermission::ManagePrices->value))
                ->should(fn (Model $user, Model $indicativePrice): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
