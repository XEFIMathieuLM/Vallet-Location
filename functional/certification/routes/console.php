<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('certification:reconcile')->everyMinute()->withoutOverlapping();
