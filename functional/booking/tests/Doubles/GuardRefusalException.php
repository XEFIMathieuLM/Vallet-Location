<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Fleet\Exceptions\RefusalException;

final class GuardRefusalException extends RefusalException
{
    public static function missingPhotos(string $userText): self
    {
        return new self('Transition refused by a test guard.', $userText);
    }
}
