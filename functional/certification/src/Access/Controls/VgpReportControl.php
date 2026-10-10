<?php

namespace Functional\Certification\Access\Controls;

use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class VgpReportControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = VgpReport::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof Authorizable && $user->can(CertificationPermission::Manage->value))
                ->should(fn (Model $user, Model $report): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
