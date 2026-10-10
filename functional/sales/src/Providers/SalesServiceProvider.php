<?php

namespace Functional\Sales\Providers;

use Functional\Sales\Access\Controls\SaleControl;
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

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
