<?php

namespace Functional\Fleet\Data;

final readonly class FleetImportReport
{
    /**
     * @param  list<array{line: int, reference: string, reason: string}>  $rejections
     */
    public function __construct(
        public int $createdCount,
        public array $rejections,
    ) {}

    public function hasRejections(): bool
    {
        return $this->rejections !== [];
    }
}
