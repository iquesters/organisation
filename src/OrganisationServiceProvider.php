<?php

namespace Iquesters\Organisation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Command;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Organisation\Config\OrganisationConf;
use Iquesters\Organisation\Database\Seeders\OrganisationSeeder;

class OrganisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // $this->mergeConfigFrom(__DIR__ . '/../config/organisation.php', 'organisation');
        ConfProvider::register(Module::ORGANISATION, OrganisationConf::class);

        $this->registerSeedCommand();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'organisation');

        if ($this->app->runningInConsole()) {
            $this->commands([
                'command.organisation.seed'
            ]);
        }
        
        $this->publishes([
            __DIR__ . '/../config/organisation.php' => config_path('organisation.php'),
            __DIR__ . '/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/organisation/layouts/package.blade.php'),
        ], 'organisation-config');
    }

    protected function registerSeedCommand(): void
    {
        $this->app->singleton('command.organisation.seed', function ($app) {
            return new class extends Command {
                protected $signature = 'organisation:seed';
                protected $description = 'Seed Organisation module data';

                public function handle()
                {
                    $this->info('Running Organisation Seeder...');
                    $seeder = new OrganisationSeeder();
                    $seeder->setCommand($this);
                    $seeder->run();
                    $this->info('Organisation seeding completed!');
                    return 0;
                }
            };
        });
    }
}