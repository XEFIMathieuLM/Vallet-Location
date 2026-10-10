<?php

namespace Functional\Certification\Exceptions;

use RuntimeException;

final class MissingGoLiveDateException extends RuntimeException
{
    public static function make(): self
    {
        return new self('The VGP certificate go-live date (CERTIFICATION_GO_LIVE_DATE) is missing or invalid: no certificate is opened.');
    }
}
