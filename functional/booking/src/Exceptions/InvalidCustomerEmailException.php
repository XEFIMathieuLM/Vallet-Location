<?php

namespace Functional\Booking\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidCustomerEmailException extends RefusalException
{
    public static function empty(): self
    {
        return new self('The customer e-mail address is empty.', 'booking::customers.refusals.email_empty');
    }

    public static function invalid(string $email): self
    {
        return new self('The customer e-mail address is not valid.', 'booking::customers.refusals.email_invalid', ['email' => $email]);
    }
}
