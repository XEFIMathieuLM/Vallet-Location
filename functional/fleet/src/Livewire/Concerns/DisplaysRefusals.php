<?php

namespace Functional\Fleet\Livewire\Concerns;

use Closure;
use Functional\Fleet\Exceptions\RefusalException;
use Throwable;

trait DisplaysRefusals
{
    public function exceptionDisplaysRefusals(Throwable $e, Closure $stopPropagation): void
    {
        if (! $e instanceof RefusalException) {
            return;
        }

        $this->addError('refusal', $e->userMessage());
        $stopPropagation();
    }
}
