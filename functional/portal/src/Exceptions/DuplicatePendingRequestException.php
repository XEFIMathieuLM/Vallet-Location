<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DuplicatePendingRequestException extends RefusalException
{
    public static function overlapping(): self
    {
        return new self(
            'A pending request of this account already covers this machine on overlapping dates.',
            'portal::refusals.duplicate_pending_request',
        );
    }
}
