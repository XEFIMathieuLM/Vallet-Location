<?php

namespace Functional\Billing\Transmissions;

use Illuminate\Database\Eloquent\Model;

final readonly class TransmissionSubject
{
    public function __construct(
        public string $label,
        public int $customerId,
        public string $customerName,
        public string $url,
        public Model $historySubject,
    ) {}
}
