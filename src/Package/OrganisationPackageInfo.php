<?php

namespace Iquesters\Organisation\Package;

use Iquesters\Foundation\System\Package\PackageInfo;

class OrganisationPackageInfo extends PackageInfo
{
    protected string $module_name = 'organisation';

    protected string $seeder_class = 'OrganisationSeeder';
}