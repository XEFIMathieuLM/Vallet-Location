<?php

namespace Functional\Portal\Data;

use Functional\Portal\Enums\CustomerChoiceKind;

final readonly class CustomerChoice
{
    private function __construct(
        public CustomerChoiceKind $kind,
        public ?int $existingCustomerId,
    ) {}

    public static function existing(int $customerId): self
    {
        return new self(CustomerChoiceKind::Existing, $customerId);
    }

    public static function create(): self
    {
        return new self(CustomerChoiceKind::Create, null);
    }
}
