<?php

namespace Iquesters\Organisation\Tests;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\ServiceProvider as BreadcrumbsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Iquesters\Foundation\FoundationServiceProvider;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\OrganisationServiceProvider;
use Iquesters\Organisation\Tests\Fixtures\User;
use Iquesters\UserInterface\UserInterfaceServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            SanctumServiceProvider::class,
            BreadcrumbsServiceProvider::class,
            FoundationServiceProvider::class,
            UserInterfaceServiceProvider::class,
            OrganisationServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return ['Breadcrumbs' => Breadcrumbs::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function makeUser(?string $role = null): User
    {
        $user = User::create([
            'name' => 'Test '.Str::random(6),
            'email' => Str::random(10).'@example.test',
            'password' => bcrypt(Str::random(20)),
        ]);

        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        return $user;
    }

    protected function makeOrganisation(string $name = 'Acme'): Organisation
    {
        return Organisation::create([
            'uid' => (string) Str::ulid(),
            'name' => $name,
            'description' => "{$name} organisation",
            'status' => 'active',
        ]);
    }
}
