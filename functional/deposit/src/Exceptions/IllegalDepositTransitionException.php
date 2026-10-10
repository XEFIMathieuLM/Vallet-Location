<?php

namespace Functional\Deposit\Exceptions;

use Functional\Deposit\States\DepositState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalDepositTransitionException extends RefusalException
{
    public static function for(DepositState $from, string $transition): self
    {
        return new self(
            "Cannot {$transition} a deposit in status [{$from->status()->value}].",
            'deposit::refusals.illegal_transition',
            ['status' => $from->status()],
        );
    }
}
