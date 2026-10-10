<?php

namespace Functional\Booking\Faker;

use Xefi\Faker\Extensions\Extension;

class BookingFakerExtension extends Extension
{
    private const array PHONE_PREFIXES = ['01', '02', '03', '04', '05', '06', '07', '09'];

    public function customerPhoneNumber(): string
    {
        return $this->pickArrayRandomElement(self::PHONE_PREFIXES).$this->formatString(' {d}{d} {d}{d} {d}{d} {d}{d}');
    }
}
