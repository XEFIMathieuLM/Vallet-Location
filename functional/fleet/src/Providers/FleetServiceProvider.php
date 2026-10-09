<?php

namespace Functional\Fleet\Providers;

use Functional\Fleet\Database\Seeders\FleetSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class FleetServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([FleetSeeder::class]);
        }

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );
        $this->loadSeeders([\Functional\Fleet\Database\Seeders\FleetSeeder::class]);
    }

    public function register(): void
    {
        //
    }
}
