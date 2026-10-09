<?php

namespace Functional\Booking\Providers;

use Functional\Booking\Database\Seeders\BookingSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class BookingServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([BookingSeeder::class]);
        }

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );
        $this->loadSeeders([\Functional\Booking\Database\Seeders\BookingSeeder::class]);
    }

    public function register(): void
    {
        //
    }
}
