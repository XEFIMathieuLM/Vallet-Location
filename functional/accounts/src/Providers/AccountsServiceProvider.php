<?php

namespace Functional\Accounts\Providers;

use Functional\Accounts\Access\Controls\KeyAccountControl;
use Functional\Accounts\Badges\KeyAccountBadgeProvider;
use Functional\Accounts\Guards\KeyAccountTypeGuard;
use Functional\Booking\Extensions\CustomerBadges;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Lomkit\Access\Access;
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

        (new Access)->addControls([new KeyAccountControl]);

        $this->app->make(CustomerChangeGuards::class)->register(KeyAccountTypeGuard::class);
        $this->app->make(CustomerBadges::class)->register(KeyAccountBadgeProvider::class);

        $this->withRouting(web: __DIR__.'/../../routes/web.php');
    }
}
