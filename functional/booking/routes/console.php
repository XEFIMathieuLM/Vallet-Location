<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('booking:flag-late-returns')->dailyAt('00:05');
