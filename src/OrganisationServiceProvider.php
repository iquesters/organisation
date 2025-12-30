<?php

namespace Iquesters\Organisation;

use Iquesters\Foundation\System\Providers\BaseServiceProvider;
use Iquesters\Foundation\System\Package\PackageInfo;
use Iquesters\Organisation\Package\OrganisationPackageInfo;

class OrganisationServiceProvider extends BaseServiceProvider
{
    protected function packageInfo(): PackageInfo
    {
        return new OrganisationPackageInfo();
    }

    // protected function seedCommandName(): string
    // {
    //     return 'organisation';
    // }

    // protected function seederClass(): string
    // {
    //     return OrganisationSeeder::class;
    // }
}