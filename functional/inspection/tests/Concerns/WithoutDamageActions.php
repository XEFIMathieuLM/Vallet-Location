<?php

namespace Functional\Inspection\Tests\Concerns;

use Functional\Inspection\Support\DamageActions;

trait WithoutDamageActions
{
    protected function setUpWithoutDamageActions(): void
    {
        $this->app->instance(DamageActions::class, new DamageActions);
    }
}
