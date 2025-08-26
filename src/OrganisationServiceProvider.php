<?php

namespace Iquesters\Organisation;

use Illuminate\Support\ServiceProvider;

class OrganisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/organisation.php', 'organisation');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'organisation');

        $this->publishes([
            __DIR__ . '/../config/organisation.php' => config_path('organisation.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/organisation/layouts/package.blade.php'),
        ], 'organisation-config');
    }
}