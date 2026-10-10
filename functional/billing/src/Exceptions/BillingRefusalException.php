<?php

namespace Functional\Billing\Exceptions;

use DomainException;

abstract class BillingRefusalException extends DomainException
{
    /**
     * @param  array<string, string>  $userMessageReplacements
     */
    final protected function __construct(
        string $developerMessage,
        private readonly string $userMessageKey,
        private readonly array $userMessageReplacements = [],
    ) {
        parent::__construct($developerMessage);
    }

    public function userMessage(): string
    {
        return __($this->userMessageKey, $this->userMessageReplacements);
    }
}
