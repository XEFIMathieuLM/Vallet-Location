<?php

namespace Functional\Certification\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum DispatchChannel: string implements HasLabel
{
    case Email = 'email';
    case Hand = 'hand';

    public function label(): string
    {
        return __("certification::enums.dispatch_channel.{$this->value}");
    }
}
