<?php

namespace App\Dashboard;

final readonly class PendingWorkCounter
{
    public function __construct(
        public string $key,
        public int $count,
        public string $url,
    ) {}

    public function isHighlighted(): bool
    {
        return $this->count > 0;
    }
}
