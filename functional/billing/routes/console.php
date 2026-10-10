<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('billing:close-months')->dailyAt('00:15')->timezone(config()->string('billing.timezone'));
