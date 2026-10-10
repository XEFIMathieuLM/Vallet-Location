<?php

namespace App\Access;

enum AppPermission: string
{
    case ManageUsers = 'users.manage';
}
