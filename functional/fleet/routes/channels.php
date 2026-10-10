<?php

use Functional\Fleet\Access\FleetPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('fleet', fn (Authorizable $user): bool => $user->can(FleetPermission::ViewFleet->value));
