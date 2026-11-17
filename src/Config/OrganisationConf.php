<?php

namespace Iquesters\Organisation\Config;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\BaseConf;

class OrganisationConf extends BaseConf
{
    // Inherited property of BaseConf, must initialize
    protected ?string $identifier = Module::ORGANISATION;

    protected string $organisation_layout;

    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->organisation_layout = 'organisation::layouts.package';
    }
}
