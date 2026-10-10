<?php

namespace Functional\Fleet\Access;

enum FleetPermission: string
{
    case ManageMachines = 'machines.manage';
    case ViewFleet = 'fleet.view';
}
