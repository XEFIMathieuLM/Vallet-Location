<?php

namespace Functional\Billing\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class NothingToExportException extends RefusalException
{
    public static function make(): self
    {
        return new self(__('billing::exports.refusals.nothing_to_export'));
    }
}
