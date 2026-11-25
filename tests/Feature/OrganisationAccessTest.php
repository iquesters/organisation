<?php

namespace Iquesters\Organisation\Tests\Feature;

use Iquesters\Organisation\Support\OrganisationForms;
use Iquesters\Organisation\Support\OrganisationTables;
use Iquesters\Organisation\Tests\TestCase;

class OrganisationAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OrganisationForms::syncAll();
        OrganisationTables::syncAll();
    }

    public function test_member_can_view_own_organisation(): void
    {
        $org = $this->makeOrganisation();
        $user = $this->makeUser('user');
        $user->organisations()->attach($org->id);

        // Members see their organisation's profile; the list is the super-admins'.
        $this->actingAs($user)->get("/organisations/{$org->uid}/show")->assertRedirect(route('organisations.profile.show', $org->uid));
        $this->get('/organisations')->assertRedirect(route('organisations.mine'));
        $this->getJson('/organisations/data')->assertForbidden();
        $this->getJson("/organisations/data/{$org->uid}")->assertForbidden();
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

        $location = $this->actingAs($this->makeUser('super-admin'))->get("/organisations/{$org->uid}/show")->assertRedirect()->headers->get('Location');

        $this->assertStringEndsWith("/data/{$org->uid}", $location);
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
        $this->actingAs($user)->postJson('/organisation-people', ['organisation_id' => $org->id, 'role' => 'member', 'email' => 'x@example.test'])
            ->assertStatus(422)->assertJsonValidationErrors('organisation_id');
        $this->actingAs($user)->get("/organisations/{$org->uid}/teams")->assertForbidden();

        $this->assertDatabaseHas('organisations', ['id' => $org->id, 'name' => 'Acme']);
        $this->assertFalse($other->organisations()->exists());
    }

    public function test_global_admin_cannot_create_or_read_unrelated_organisations(): void
    {
        $org = $this->makeOrganisation('Foreign');
        $user = $this->makeUser('admin');
        $this->actingAs($user)->postJson('/organisations', ['name' => 'Denied'])->assertForbidden();
        $this->get('/organisations/'.$org->uid.'/show')->assertRedirect(route('organisations.index'));
        $this->putJson('/organisations/'.$org->uid, ['name' => 'Denied'])->assertForbidden();
        $this->assertDatabaseHas('organisations', ['id' => $org->id, 'name' => 'Foreign']);
    }

    public function test_only_creating_and_changing_organisations_carry_ensure_admin(): void
    {
        // Super-admin only. Everything else checks access itself (OrganisationAccess:
        // members view, org-admins manage their own organisations' people and teams).
        // organisations.update: super-admins and the organisation's org-admins (checked in the controller).
        $superAdminOnly = ['organisations.create', 'organisations.store', 'organisations.edit', 'organisations.destroy'];

        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'organisations.'));

        $this->assertGreaterThan(10, $routes->count());

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            if (in_array($route->getName(), $superAdminOnly, true)) {
                $this->assertContains('ensure.admin', $middleware, "Route [{$route->getName()}] is missing ensure.admin.");
            } else {
                $this->assertNotContains('ensure.admin', $middleware, "Route [{$route->getName()}] should check access itself.");
            }
        }
    }
}
