<?php

namespace Functional\Inspection\Livewire\Concerns;

use Flux\Flux;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\Gate;

trait ResolvesDamages
{
    public function resolveDamage(int $damageId): void
    {
        Gate::authorize(InspectionPermission::ManageDamages->value);

        app(ResolveDamage::class)->handle(Damage::query()->findOrFail($damageId), $this->agencyMember());

        Flux::modal("resolve-damage-{$damageId}")->close();
    }
}
