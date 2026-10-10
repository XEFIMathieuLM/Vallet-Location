<?php

namespace Functional\Certification\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class CertificationServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/certification.php', 'certification');
        $this->overrideConfigFrom(__DIR__.'/../../config/filesystems.php', 'filesystems');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'certification');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'certification');
    }
}
