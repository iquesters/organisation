<?php

namespace Iquesters\Organisation\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Iquesters\Organisation\Traits\HasOrganisations;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, HasOrganisations;

    protected $table = 'users';

    protected $guarded = [];

    protected $guard_name = 'web';
}
