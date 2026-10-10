<?php

namespace Functional\Certification\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum DispatchOutcome: string implements HasLabel
{
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return __("certification::enums.dispatch_outcome.{$this->value}");
    }
}
