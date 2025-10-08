<?php

namespace Iquesters\Organisation\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseModuleSeeder;

class OrganisationSeeder extends BaseModuleSeeder
{
    protected string $moduleName = 'organisation';
    protected string $description = 'organisation module';
    protected array $metas = [
        'module_icon' => 'fas fa-building',
        'module_sidebar_menu' => [
            [
                "icon" => "fas fa-building-columns",
                "label" => "All Organisations",
                "route" => "organisations.index",
            ]
        ]
    ];

    protected array $permissions = [
        'view-organisations',
        'create-organisations',
        'edit-organisations',
        'delete-organisations'
    ];
}