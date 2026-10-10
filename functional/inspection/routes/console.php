<?php

use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [Photo::class, PhotoSession::class]])->dailyAt('02:15');
