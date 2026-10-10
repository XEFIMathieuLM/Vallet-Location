<?php

namespace Functional\Portal\Enums;

enum PortalHistoryEvent: string
{
    case RequestSent = 'request_sent';
    case RequestConfirmed = 'request_confirmed';
    case AccountAttached = 'account_attached';
    case RequestRefused = 'request_refused';
    case RequestCancelled = 'request_cancelled';
    case RequestExpired = 'request_expired';
    case IndicativePriceSet = 'indicative_price_set';
    case IndicativePriceRemoved = 'indicative_price_removed';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function description(array $details): string
    {
        return __("portal::requests.history.{$this->value}", array_map(fn (string|int|null $detail): string => (string) $detail, $details));
    }
}
