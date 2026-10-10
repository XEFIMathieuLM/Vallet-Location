<?php

namespace Functional\Portal\Data;

final readonly class PortalMachineOffer
{
    public function __construct(
        public int $machineId,
        public string $reference,
        public string $categoryName,
        public string $agencyName,
        public ?int $dailyPriceCents,
    ) {}
}
