<?php

namespace Functional\Sales\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class SalesServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'sales');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'sales');

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
