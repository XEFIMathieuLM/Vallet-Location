<?php

namespace Functional\Sales\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Models\Sale;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class SaleControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = Sale::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(SalesPermission::Manage->value))
                ->should(fn (Model $user, Model $sale): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
