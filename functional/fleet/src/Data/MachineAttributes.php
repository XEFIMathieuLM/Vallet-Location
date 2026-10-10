<?php

namespace Functional\Fleet\Data;

use Carbon\CarbonImmutable;

final readonly class MachineAttributes
{
    public function __construct(
        public string $reference,
        public int $categoryId,
        public int $agencyId,
        public bool $isSubjectToVgp,
        public ?CarbonImmutable $vgpDueDate,
    ) {}
}
