<?php

namespace Functional\Inspection\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class CategoryViewControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = CategoryView::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(InspectionPermission::ManageInspectionViews->value))
                ->should(fn (Model $user, Model $categoryView): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
