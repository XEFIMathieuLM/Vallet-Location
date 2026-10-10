<?php

namespace Functional\Sales\Enums;

enum SaleHistoryEvent: string
{
    case Listed = 'listed';
    case AskingPriceChanged = 'asking_price_changed';
    case DescriptionChanged = 'description_changed';
    case OfferRecorded = 'offer_recorded';
    case OfferAccepted = 'offer_accepted';
    case OfferRejected = 'offer_rejected';
    case OfferWithdrawn = 'offer_withdrawn';
    case HandoverDateChanged = 'handover_date_changed';
    case ReservationReleased = 'reservation_released';
    case HandedOver = 'handed_over';
    case Cancelled = 'cancelled';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function description(array $details): string
    {
        return __("sales::history.{$this->value}", array_map(fn (string|int|null $detail): string => (string) $detail, $details));
    }
}
