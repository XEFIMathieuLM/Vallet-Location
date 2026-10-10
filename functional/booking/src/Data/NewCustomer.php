<?php

namespace Functional\Booking\Data;

use Functional\Booking\Enums\CustomerType;

final readonly class NewCustomer
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
        public CustomerType $type,
    ) {}
}
