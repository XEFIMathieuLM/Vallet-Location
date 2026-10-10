<?php

namespace Functional\Accounts\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class AccountsServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/accounts.php', 'accounts');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'accounts');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'accounts');

        $this->withRouting(web: __DIR__.'/../../routes/web.php');
    }
}
