<?php

namespace Functional\Portal\Providers;

use Functional\Portal\Access\Controls\IndicativePriceControl;
use Functional\Portal\Access\Controls\ReservationRequestControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class PortalServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/portal.php', 'portal');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'portal');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'portal');

        (new Access)->addControls([new ReservationRequestControl, new IndicativePriceControl]);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
