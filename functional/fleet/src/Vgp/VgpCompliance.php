<?php

namespace Functional\Fleet\Vgp;

use Carbon\CarbonImmutable;

final readonly class VgpCompliance
{
    public function __construct(
        private bool $isSubjectToVgp,
        private ?CarbonImmutable $vgpDueDate,
    ) {}

    public function coversUntil(CarbonImmutable $endDate): bool
    {
        if (! $this->isSubjectToVgp) {
            return true;
        }

        return $this->vgpDueDate !== null && $this->vgpDueDate->gte($endDate->startOfDay());
    }
}
