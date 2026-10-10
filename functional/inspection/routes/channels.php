<?php

use Functional\Booking\Access\BookingPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('reservation.{reservation}', fn (Authorizable $user): bool => $user->can(BookingPermission::ManageReservations->value));
