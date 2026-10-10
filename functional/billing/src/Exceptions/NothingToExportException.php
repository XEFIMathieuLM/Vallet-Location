<?php

namespace Functional\Billing\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class NothingToExportException extends RefusalException
{
    public static function make(): self
    {
        return new self('No pending or failed transmission to export.', 'billing::exports.refusals.nothing_to_export');
    }
}
