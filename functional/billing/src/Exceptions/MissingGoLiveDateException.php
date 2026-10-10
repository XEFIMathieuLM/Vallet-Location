<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class MissingGoLiveDateException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('billing::transmissions.missing_go_live_date'));
    }
}
