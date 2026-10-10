<?php

namespace Functional\Booking\Data;

final readonly class NewCustomer
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
    ) {}
}
