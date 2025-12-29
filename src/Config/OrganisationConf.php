<?php

namespace Iquesters\Organisation\Config;

use Iquesters\Foundation\Support\BaseConf;
use Iquesters\Foundation\Support\ApiConf;
use Iquesters\Foundation\Enums\Module;

class OrganisationConf extends BaseConf
{
    // Inherited property of BaseConf, must initialize
    protected ?string $identifier = Module::ORGANISATION;
    
    
    // properties of this class
    protected string $organisation_layout;
    
    protected ApiConf $api_conf;
    
    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->organisation_layout = 'organisation::layouts.package';

        $default_values->api_conf = new ApiConf();
        $default_values->api_conf->prefix = 'organisation'; // Must be auto generated from module enum - the vendor name  
        $default_values->api_conf->prepareDefault($default_values->api_conf);
    }
}