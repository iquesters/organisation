<?php

namespace Iquesters\Organisation\Config;

use Iquesters\Foundation\Support\BaseConf;
use Iquesters\Foundation\Enums\Module;

class OrganisationConf extends BaseConf
{
    // Inherited property of BaseConf, must initialize
    protected ?string $identifier = Module::ORGANISATION;
    
    // properties of this class
    protected string $organisation_layout;
    
    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->organisation_layout = 'organisation::layouts.package';
    }
}