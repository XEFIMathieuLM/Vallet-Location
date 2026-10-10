<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DamageNotReportableException extends RefusalException
{
    public static function returnPhotosIncomplete(): self
    {
        return new self(__('inspection::damages.refusals.return_photos_incomplete'));
    }

    public static function emptyComment(): self
    {
        return new self(__('inspection::damages.refusals.empty_comment'));
    }
}
