<?php

use Functional\Inspection\Models\Photo;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [Photo::class]])->dailyAt('02:15');
