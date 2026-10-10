<?php

namespace Functional\Fleet\Data;

final readonly class MachineBadge
{
    public function __construct(
        public string $label,
        public string $color,
        public ?string $url = null,
    ) {}
}
