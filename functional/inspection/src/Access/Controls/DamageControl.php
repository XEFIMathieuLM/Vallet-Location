<?php

namespace Functional\Inspection\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Models\Damage;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class DamageControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = Damage::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(InspectionPermission::ManageDamages->value))
                ->should(fn (Model $user, Model $damage): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
