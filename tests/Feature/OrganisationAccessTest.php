<?php

namespace Iquesters\Organisation\Tests\Feature;

use Iquesters\Organisation\Tests\TestCase;

class OrganisationAccessTest extends TestCase
{
    public function test_member_can_view_own_organisation(): void
    {
        $org = $this->makeOrganisation();
        $user = $this->makeUser('user');
        $user->organisations()->attach($org->id);

        $response = $this->actingAs($user)->get("/organisations/{$org->uid}/show");

        $this->assertFalse($response->isRedirect(), 'Member was redirected away from their organisation.');
    }

    public function test_non_member_cannot_view_another_organisation(): void
    {
        $org = $this->makeOrganisation('Other');

        $this->actingAs($this->makeUser('user'))
            ->get("/organisations/{$org->uid}/show")
            ->assertRedirect(route('organisations.index'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_view_any_organisation(): void
    {
        $org = $this->makeOrganisation();

        $response = $this->actingAs($this->makeUser('super-admin'))->get("/organisations/{$org->uid}/show");

        $this->assertFalse($response->isRedirect(), 'Admin was redirected away from the organisation.');
    }

    public function test_non_admin_cannot_manage_organisations(): void
    {
        $org = $this->makeOrganisation();
        $user = $this->makeUser('user');
        $user->organisations()->attach($org->id);
        $other = $this->makeUser('user');

        $this->actingAs($user)->get('/organisations/create')->assertForbidden();
        $this->actingAs($user)->post('/organisations', ['name' => 'New'])->assertForbidden();
        $this->actingAs($user)->put("/organisations/{$org->uid}", ['name' => 'Renamed'])->assertForbidden();
        $this->actingAs($user)->delete("/organisations/{$org->uid}")->assertForbidden();
        $this->actingAs($user)->get("/organisations/{$org->uid}/users")->assertForbidden();
        $this->actingAs($user)->post("/organisations/{$org->uid}/users", ['user_id' => 'x'])->assertForbidden();
        $this->actingAs($user)->get("/organisations/{$org->uid}/teams")->assertForbidden();

        $this->assertDatabaseHas('organisations', ['id' => $org->id, 'name' => 'Acme']);
        $this->assertFalse($other->organisations()->exists());
    }

    public function test_management_routes_carry_ensure_admin(): void
    {
        $open = ['organisations.index', 'organisations.show'];

        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'organisations.'));

        $this->assertGreaterThan(10, $routes->count());

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            if (in_array($route->getName(), $open, true)) {
                $this->assertNotContains('ensure.admin', $middleware, "Route [{$route->getName()}] should be open to members.");
            } else {
                $this->assertContains('ensure.admin', $middleware, "Route [{$route->getName()}] is missing ensure.admin.");
            }
        }
    }
}
