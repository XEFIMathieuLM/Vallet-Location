<?php

namespace Functional\Certification\Exceptions;

use Functional\Certification\States\CertificateState;
use Functional\Fleet\Exceptions\RefusalException;

final class IllegalCertificateTransitionException extends RefusalException
{
    public static function for(CertificateState $from, string $transition): self
    {
        return new self(
            "Cannot {$transition} a VGP certificate in status [{$from->status()->value}].",
            'certification::certificates.refusals.illegal_transition',
            ['transition' => (string) __("certification::certificates.transitions.{$transition}"), 'status' => $from->status()],
        );
    }
}
