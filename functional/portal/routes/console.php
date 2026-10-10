<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('portal:reconcile')->everyFiveMinutes()->withoutOverlapping();
