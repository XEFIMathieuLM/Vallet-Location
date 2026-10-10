<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class PhotoSessionUnavailableException extends RefusalException
{
    public static function make(): self
    {
        return new self(__('inspection::photos.refusals.link_unavailable'));
    }
}
