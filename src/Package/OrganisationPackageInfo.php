<?php

namespace Iquesters\Organisation\Package;

use Iquesters\Foundation\System\Package\PackageInfo;

class OrganisationPackageInfo extends PackageInfo
{
    protected function definePackageInfo(): void
    {
        $this->laravel_config_name = 'organisation';

        $this->specific_providers = [
            OrganisationServiceProvider::class,
        ];

        $this->specific_commands = [
            OrganisationSeederCommand::class,
        ];

        $this->specific_models = [
            Organisation::class,
            Department::class,
        ];

        $this->custom_views_path = $this->getPackagePath() . '/custom/views';
    }
}
