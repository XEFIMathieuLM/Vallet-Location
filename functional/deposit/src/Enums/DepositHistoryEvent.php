<?php

namespace Functional\Deposit\Enums;

enum DepositHistoryEvent: string
{
    case Collected = 'collected';
    case PaymentCorrected = 'payment_corrected';
    case BlockedByDamage = 'blocked_by_damage';
    case Released = 'released';
    case Refunded = 'refunded';
    case Settled = 'settled';

    /**
     * @param  array<string, string|int|bool|null>  $details
     */
    public function description(array $details): string
    {
        $replacements = array_map(fn (string|int|bool|null $detail): string => (string) $detail, $details);

        return __("deposit::history.{$this->value}", $replacements);
    }
}
