<?php

use Functional\Sales\Access\SalesPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('sales', fn (Authorizable $user): bool => $user->can(SalesPermission::Manage->value));
