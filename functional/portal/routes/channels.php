<?php

use Functional\Portal\Access\PortalPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('portal-requests', fn (Authorizable $user): bool => $user->can(PortalPermission::HandleRequests->value));
