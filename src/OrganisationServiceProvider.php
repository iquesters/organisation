<?php

namespace Iquesters\Organisation;

use Iquesters\Foundation\Support\UserModelPatcher;
use Iquesters\Foundation\System\Providers\BaseServiceProvider;
use Iquesters\Organisation\Traits\HasOrganisations;

class OrganisationServiceProvider extends BaseServiceProvider
{
    // Routes, views, migrations, seeder command and conf are auto-detected by BaseServiceProvider.

    public function boot(): void
    {
        parent::boot();

        // composer install/update: add HasOrganisations to the app's user model (idempotent).
        $this->onPackageDiscover(function () {
            foreach (UserModelPatcher::ensure([HasOrganisations::class]) as $change) {
                echo "👤 User model (organisation): {$change}" . PHP_EOL;
            }
        });
    }
}
