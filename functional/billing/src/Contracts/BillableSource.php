<?php

namespace Functional\Billing\Contracts;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Transmissions\TransmissionSubject;
use Illuminate\Support\Collection;

interface BillableSource
{
    public function type(): BillableLineType;

    public function line(Transmission $transmission): BillableLine;

    /**
     * @param  Collection<int, Transmission>  $transmissions
     * @return array<int, TransmissionSubject>
     */
    public function subjects(Collection $transmissions): array;
}
