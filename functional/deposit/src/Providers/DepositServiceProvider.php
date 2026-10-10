<?php

namespace Functional\Deposit\Providers;

use Functional\Deposit\Access\Controls\DepositControl;
use Functional\Deposit\Access\Controls\DepositRateControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class DepositServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deposit.php', 'deposit');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'deposit');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'deposit');

        (new Access)->addControls([new DepositControl, new DepositRateControl]);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            commands: __DIR__.'/../../routes/console.php',
        );
    }
}
