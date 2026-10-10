<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class PruneScheduleTest extends TestCase
{
    public function test_model_prune_runs_daily_on_the_models_registered_by_the_layers(): void
    {
        $pruneEvents = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'model:prune'));

        $this->assertCount(1, $pruneEvents);
        $this->assertSame('0 0 * * *', $pruneEvents->first()?->expression);
        $this->assertIsArray(config('prunable.models'));
    }
}
