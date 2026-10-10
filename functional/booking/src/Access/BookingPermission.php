<?php

namespace Functional\Booking\Access;

enum BookingPermission: string
{
    case ManageReservations = 'reservations.manage';
}
