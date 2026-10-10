<?php

namespace Functional\Billing\Enums;

enum BillingHistoryEvent: string
{
    case PeriodCreated = 'period_created';
    case Sent = 'sent';
    case Failed = 'failed';
    case Unreachable = 'unreachable';
    case Retried = 'retried';
    case Exported = 'exported';
    case DamageBilled = 'damage_billed';
    case DamageWaived = 'damage_waived';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function description(array $details): string
    {
        return __("billing::history.{$this->value}", array_map(fn (string|int|null $detail): string => (string) $detail, $details));
    }
}
