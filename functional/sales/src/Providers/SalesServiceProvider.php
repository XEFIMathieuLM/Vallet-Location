<?php

namespace Functional\Sales\Providers;

use Functional\Fleet\Extensions\MachineBadges;
use Functional\Sales\Access\Controls\SaleControl;
use Functional\Sales\Badges\SaleMachineBadges;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class SalesServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'sales');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'sales');

        (new Access)->addControls([new SaleControl]);
        $this->app->make(MachineBadges::class)->register(SaleMachineBadges::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
