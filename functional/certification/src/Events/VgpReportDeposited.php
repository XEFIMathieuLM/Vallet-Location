<?php

namespace Functional\Certification\Events;

use Functional\Certification\Models\VgpReport;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class VgpReportDeposited implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly VgpReport $report) {}
}
