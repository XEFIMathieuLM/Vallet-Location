<?php

namespace Functional\Inspection\Livewire\Concerns;

use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait ResolvesDamages
{
    public function resolveDamage(int $damageId): void
    {
        Gate::authorize('damages.manage');

        app(ResolveDamage::class)->handle(Damage::query()->findOrFail($damageId), Auth::user() ?? abort(401));
    }
}
