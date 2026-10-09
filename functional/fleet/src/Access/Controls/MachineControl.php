<?php

namespace Functional\Fleet\Access\Controls;

use Functional\Fleet\Access\Perimeters\GlobalPerimeter;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;

class MachineControl extends Control
{
    /**
     * @var class-string<Model>
     */
    protected string $model = Machine::class;

    protected function perimeters(): array
    {
        return [
            GlobalPerimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user->can('machines.manage'))
                ->should(fn (Model $user, Model $machine): bool => true)
                ->query(fn (Builder $query, Model $user): Builder => $query),
        ];
    }
}
