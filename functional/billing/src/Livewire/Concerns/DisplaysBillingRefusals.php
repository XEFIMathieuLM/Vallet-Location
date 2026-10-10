<?php

namespace Functional\Billing\Livewire\Concerns;

use Closure;
use Functional\Billing\Exceptions\BillingRefusalException;
use Throwable;

trait DisplaysBillingRefusals
{
    /**
     * Livewire calls this hook with named arguments, hence the short parameter name.
     */
    public function exceptionDisplaysBillingRefusals(Throwable $e, Closure $stopPropagation): void
    {
        if (! $e instanceof BillingRefusalException) {
            return;
        }

        $this->addError('refusal', $e->userMessage());
        $stopPropagation();
    }
}
