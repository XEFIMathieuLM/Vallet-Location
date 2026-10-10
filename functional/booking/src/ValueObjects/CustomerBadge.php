<?php

namespace Functional\Booking\ValueObjects;

final readonly class CustomerBadge
{
    public function __construct(
        public string $label,
        public string $color,
        public ?string $url = null,
        public ?string $description = null,
    ) {}
}
