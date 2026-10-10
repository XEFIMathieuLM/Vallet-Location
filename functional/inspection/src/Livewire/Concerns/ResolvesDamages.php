<?php

namespace Functional\Inspection\Livewire\Concerns;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Models\Damage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

trait ResolvesDamages
{
    public function resolveDamage(int $damageId): void
    {
        Gate::authorize('damages.manage');

        app(ResolveDamage::class)->handle(Damage::query()->findOrFail($damageId), Auth::user() ?? abort(401));
    }

    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if ($e instanceof RefusalException) {
            $this->addError('refusal', $e->getMessage());
            $stopPropagation();
        }
    }
}
