<?php

namespace Functional\Billing\Exceptions;

use DomainException;

final class TransmissionWithoutSourceException extends DomainException
{
    public static function for(int $transmissionId): self
    {
        return new self("Transmission #{$transmissionId} has neither a billable period nor a damage settlement.");
    }
}
