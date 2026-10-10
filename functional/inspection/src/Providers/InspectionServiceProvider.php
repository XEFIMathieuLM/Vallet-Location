<?php

namespace Functional\Inspection\Providers;

use Functional\Inspection\Access\Controls\CategoryViewControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class InspectionServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/inspection.php', 'inspection');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'inspection');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'inspection');

        (new Access)->addControls([new CategoryViewControl]);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
