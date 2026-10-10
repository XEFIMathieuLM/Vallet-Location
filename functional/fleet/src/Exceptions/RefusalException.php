<?php

namespace Functional\Fleet\Exceptions;

use DomainException;
use Functional\Fleet\Contracts\HasLabel;

abstract class RefusalException extends DomainException
{
    /**
     * @param  array<string, string|int|HasLabel>  $replacements
     */
    protected function __construct(
        string $technicalMessage,
        private readonly string $translationKey,
        private readonly array $replacements = [],
        private readonly ?int $pluralCount = null,
    ) {
        parent::__construct($technicalMessage);
    }

    public function userMessage(): string
    {
        $replacements = array_map(
            fn (string|int|HasLabel $replacement): string => $replacement instanceof HasLabel ? $replacement->label() : (string) $replacement,
            $this->replacements,
        );

        if ($this->pluralCount !== null) {
            return trans_choice($this->translationKey, $this->pluralCount, $replacements);
        }

        $userMessage = __($this->translationKey, $replacements);

        return is_string($userMessage) ? $userMessage : $this->translationKey;
    }
}
