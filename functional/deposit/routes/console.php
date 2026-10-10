<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('deposit:reconcile')->everyFiveMinutes()->withoutOverlapping();
