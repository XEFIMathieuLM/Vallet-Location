<?php

namespace Functional\Inspection\Access;

enum InspectionPermission: string
{
    case ManageDamages = 'damages.manage';
    case ManageInspectionViews = 'inspection_views.manage';
}
