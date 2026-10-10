<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class RefusalReasonRequiredException extends RefusalException
{
    public static function missing(): self
    {
        return new self('A refusal needs a reason.', 'portal::refusals.reason_required');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self("A refusal reason is limited to {$maxLength} characters.", 'portal::refusals.reason_too_long', ['max' => $maxLength]);
    }
}
