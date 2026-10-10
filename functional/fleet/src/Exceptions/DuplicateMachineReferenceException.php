<?php

namespace Functional\Fleet\Exceptions;

final class DuplicateMachineReferenceException extends RefusalException
{
    public static function for(string $reference): self
    {
        return new self(__('fleet::machines.refusals.duplicate_reference', ['reference' => $reference]));
    }
}
