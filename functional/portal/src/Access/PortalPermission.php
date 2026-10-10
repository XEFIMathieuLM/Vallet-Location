<?php

namespace Functional\Portal\Access;

enum PortalPermission: string
{
    case HandleRequests = 'portal.handle-requests';
    case ManagePrices = 'portal.manage-prices';
}
