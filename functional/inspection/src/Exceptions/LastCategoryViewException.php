<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class LastCategoryViewException extends RefusalException
{
    public static function make(): self
    {
        return new self(__('inspection::views.refusals.last_view'));
    }
}
