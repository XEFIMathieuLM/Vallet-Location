<?php

namespace Functional\Portal\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class PendingRequestLimitReachedException extends RefusalException
{
    public static function of(int $maxPendingRequests): self
    {
        return new self(
            "The account already has {$maxPendingRequests} pending requests.",
            'portal::refusals.pending_request_limit',
            ['max' => $maxPendingRequests],
        );
    }
}
