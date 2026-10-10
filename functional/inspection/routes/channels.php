<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('reservation.{reservation}', fn (User $user): bool => $user->can('reservations.manage'));
