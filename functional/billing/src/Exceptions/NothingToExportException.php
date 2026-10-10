<?php

namespace Functional\Billing\Exceptions;

final class NothingToExportException extends BillingRefusalException
{
    public static function make(): self
    {
        return new self('No pending or failed transmission to export.', 'billing::exports.refusals.nothing_to_export');
    }
}
