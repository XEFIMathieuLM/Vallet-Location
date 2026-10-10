<?php

namespace Functional\Fleet\Providers;

use Functional\Fleet\Access\Controls\MachineControl;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class FleetServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MachineRetirementGuards::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'fleet');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'fleet');

        (new Access)->addControls([new MachineControl]);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
