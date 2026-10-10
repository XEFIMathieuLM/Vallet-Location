<?php

namespace Functional\Billing\Access\Controls;

use App\Models\User;
use Functional\Billing\Models\BillingExport;
use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class BillingExportControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = BillingExport::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user instanceof User && $user->can('billing.manage'))
                ->should(fn (Model $user, Model $billingExport): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
