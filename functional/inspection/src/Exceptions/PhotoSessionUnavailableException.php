<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class PhotoSessionUnavailableException extends RefusalException
{
    public static function make(): self
    {
        return new self(
            'The photo link is unknown, expired, revoked or its step is closed.',
            'inspection::photos.refusals.link_unavailable',
        );
    }
}
