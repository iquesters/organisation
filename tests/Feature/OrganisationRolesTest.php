<?php

namespace Iquesters\Organisation\Tests\Feature;

use Iquesters\Organisation\Models\OrganisationUserRole;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationForms;
use Iquesters\Organisation\Support\OrganisationRoles;
use Iquesters\Organisation\Support\OrganisationTables;
use Iquesters\Organisation\Tests\Fixtures\User;
use Iquesters\Organisation\Tests\TestCase;

/** Bug #6: org-admin is a role per organisation; elsewhere the person is only what their role there says. */
class OrganisationRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OrganisationForms::syncAll();
        OrganisationTables::syncAll();
    }

    public function test_an_org_admin_who_joins_another_organisation_is_only_a_member_there(): void
    {
        $ttv = $this->makeOrganisation('TTV');
        $second = $this->makeOrganisation('Second');
        $secretary = $this->makeUser();

        OrganisationRoles::join($secretary, $ttv, OrganisationUserRole::ROLE_ORG_ADMIN);
        // Joins the second school as an alumnus (e.g. a membership approval).
        $secretary->assignOrganisation($second);

        $this->assertSame([$ttv->id], OrganisationAccess::adminOrganisationIds($secretary));
        $this->assertTrue(OrganisationAccess::canView($secretary, $second));
        $this->assertSame('member', OrganisationRoles::roleIn($secretary, $second));
        // The global menu role follows the per-organisation role.
        $this->assertTrue($secretary->fresh()->hasRole('admin'));

        // Until their role there is changed.
        OrganisationRoles::setRole($secretary, $second, OrganisationUserRole::ROLE_ORG_ADMIN);
        $this->assertEqualsCanonicalizing([$ttv->id, $second->id], OrganisationAccess::adminOrganisationIds($secretary));
    }

    public function test_the_global_admin_role_grants_no_organisation(): void
    {
        $organisation = $this->makeOrganisation();
        $user = $this->makeUser('admin');
        $user->organisations()->attach($organisation->id);

        $this->assertSame([], OrganisationAccess::adminOrganisationIds($user));
    }

    public function test_an_org_admin_adds_people_changes_roles_and_removes_them_in_their_organisation_only(): void
    {
        $mine = $this->makeOrganisation('Mine');
        $other = $this->makeOrganisation('Other');
        $admin = $this->makeUser();
        OrganisationRoles::join($admin, $mine, OrganisationUserRole::ROLE_ORG_ADMIN);
        $existing = $this->makeUser();
        $this->actingAs($admin);

        // An existing account, by email (any case).
        $this->postJson('/organisation-people', ['organisation_id' => $mine->id, 'role' => 'member', 'email' => strtoupper($existing->email)])
            ->assertCreated()->assertJsonPath('data.role', 'member');
        $this->assertTrue($existing->hasOrganisation($mine));

        // A new account needs a name.
        $this->postJson('/organisation-people', ['organisation_id' => $mine->id, 'role' => 'member', 'email' => 'new@ttv.test'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/organisation-people', ['organisation_id' => $mine->id, 'role' => 'org-admin', 'email' => 'new@ttv.test', 'name' => 'New Person'])
            ->assertCreated()->assertJsonPath('data.role_label', 'Organisation admin');
        $new = User::where('email', 'new@ttv.test')->firstOrFail();
        $this->assertTrue($new->hasRole('user'));
        $this->assertTrue($new->hasRole('admin'));

        // Already in it.
        $this->postJson('/organisation-people', ['organisation_id' => $mine->id, 'role' => 'member', 'email' => $existing->email])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        // Another organisation: not theirs.
        $this->postJson('/organisation-people', ['organisation_id' => $other->id, 'role' => 'member', 'email' => $existing->email])
            ->assertStatus(422)->assertJsonValidationErrors('organisation_id');

        // Change the new person's role back to member: the menu role goes.
        $row = OrganisationUserRole::where('user_id', $new->id)->firstOrFail();
        $this->putJson("/organisation-people/{$row->uid}", ['role' => 'member'])->assertOk();
        $this->assertFalse($new->fresh()->hasRole('admin'));

        // Remove: membership and role go together.
        $this->deleteJson("/organisation-people/{$row->uid}")->assertOk();
        $this->assertFalse($new->fresh()->hasOrganisation($mine));
        $this->assertDatabaseMissing('organisation_user_roles', ['id' => $row->id]);

        // The list shows only their organisation.
        $this->getJson('/organisation-people/data')->assertOk()->assertJsonCount(2, 'response_schema.data');
    }

    public function test_an_org_admin_cannot_change_or_remove_themselves_or_reach_other_organisations(): void
    {
        $mine = $this->makeOrganisation('Mine');
        $other = $this->makeOrganisation('Other');
        $admin = $this->makeUser();
        OrganisationRoles::join($admin, $mine, OrganisationUserRole::ROLE_ORG_ADMIN);
        $stranger = $this->makeUser();
        $theirs = OrganisationRoles::join($stranger, $other);
        $self = OrganisationUserRole::where('user_id', $admin->id)->firstOrFail();
        $this->actingAs($admin);

        $this->putJson("/organisation-people/{$self->uid}", ['role' => 'member'])->assertStatus(422);
        $this->deleteJson("/organisation-people/{$self->uid}")->assertStatus(422);
        $this->putJson("/organisation-people/{$theirs->uid}", ['role' => 'org-admin'])->assertNotFound();
        $this->deleteJson("/organisation-people/{$theirs->uid}")->assertNotFound();
        $this->get("/organisations/{$other->uid}/users")->assertForbidden();
    }

    public function test_removing_a_membership_directly_also_removes_the_role(): void
    {
        $organisation = $this->makeOrganisation();
        $user = $this->makeUser();
        OrganisationRoles::join($user, $organisation, OrganisationUserRole::ROLE_ORG_ADMIN);

        // Detached without the module's helpers: the foreign key cascades.
        $user->organisations()->detach($organisation->id);

        $this->assertDatabaseMissing('organisation_user_roles', ['user_id' => $user->id]);
    }

    public function test_memberships_without_a_role_row_get_one_as_member(): void
    {
        $organisation = $this->makeOrganisation();
        $user = $this->makeUser();
        $user->organisations()->attach($organisation->id);

        OrganisationRoles::heal([$organisation->id]);

        $this->assertSame('member', OrganisationRoles::roleIn($user, $organisation));
    }

    public function test_teams_hold_people_of_their_organisation_only(): void
    {
        $organisation = $this->makeOrganisation();
        $outsider = $this->makeUser();
        $person = $this->makeUser();
        $person->assignOrganisation($organisation);
        $this->actingAs($this->makeUser('super-admin'));

        $uid = $this->postJson('/organisation-teams', ['organisation_id' => $organisation->id, 'name' => 'Reunion committee'])
            ->assertCreated()->json('data.uid');
        $this->putJson("/organisation-teams/{$uid}", [
            'organisation_id' => $organisation->id, 'name' => 'Reunion committee', 'user_uids' => [$person->uid, $outsider->uid],
        ])->assertOk()->assertJsonPath('data.people', 1);
        $this->getJson("/organisation-teams/options/people?team={$uid}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_org_admin_edits_their_organisation_but_not_its_status(): void
    {
        $mine = $this->makeOrganisation('Mine');
        $other = $this->makeOrganisation('Other');
        $admin = $this->makeUser();
        OrganisationRoles::join($admin, $mine, OrganisationUserRole::ROLE_ORG_ADMIN);
        $this->actingAs($admin);

        // The All Organisations list is the super-admins' (its record says who may edit, bug #12).
        $this->getJson("/organisations/data/{$mine->uid}")->assertForbidden();
        $this->actingAs($this->makeUser('super-admin'))->getJson("/organisations/data/{$mine->uid}")->assertOk()->assertJsonPath('response_schema.data._can.edit', true);
        $this->actingAs($admin);

        $this->putJson("/organisations/{$mine->uid}", ['name' => 'Renamed', 'status' => 'inactive'])->assertOk();
        $this->assertSame(['Renamed', 'active'], [$mine->fresh()->name, $mine->fresh()->status]);
        $this->putJson("/organisations/{$other->uid}", ['name' => 'Nope'])->assertForbidden();
    }

    public function test_people_rows_say_who_may_be_changed_and_super_admins_pick_accounts(): void
    {
        $organisation = $this->makeOrganisation();
        $admin = $this->makeUser();
        $self = OrganisationRoles::join($admin, $organisation, OrganisationUserRole::ROLE_ORG_ADMIN);
        $other = OrganisationRoles::join($this->makeUser(), $organisation);
        $this->actingAs($admin);

        $this->getJson("/organisation-people/data/{$self->uid}")->assertJsonPath('response_schema.data.can_manage', '0');
        $this->getJson("/organisation-people/data/{$other->uid}")->assertJsonPath('response_schema.data.can_manage', '1');
        $this->get("/organisation-people/{$self->uid}/summary")->assertOk()->assertSee('This is you');
        // Org-admins type an email; they can't list every account.
        $this->getJson('/organisation-people/options/users')->assertOk()->assertJsonCount(1, 'data');

        $super = $this->makeUser('super-admin');
        $newcomer = $this->makeUser();
        $this->actingAs($super)->getJson('/organisation-people/options/users')->assertOk()->assertJsonFragment(['value' => $newcomer->uid]);
        $this->postJson('/organisation-people', ['organisation_id' => $organisation->id, 'role' => 'member', 'user_uid' => $newcomer->uid])
            ->assertCreated();
        $this->assertTrue($newcomer->hasOrganisation($organisation));
        $this->postJson('/organisation-people', ['organisation_id' => $organisation->id, 'role' => 'member'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_the_profile_shows_the_public_link_by_uid_and_the_policy_links(): void
    {
        $organisation = $this->makeOrganisation('TTV Alumni');
        $this->actingAs($this->makeUser('super-admin'));
        \Iquesters\Organisation\Support\OrganisationProfile::syncForm();

        $this->putJson("/organisations/{$organisation->uid}/profile", ['public_page' => '1', 'terms_text' => 'Be kind.'])
            ->assertOk();

        $this->get("/organisations/{$organisation->uid}/profile")->assertOk()
            ->assertSee(route('organisations.public', $organisation->uid))
            ->assertSee('Copy link')
            ->assertSee(url("/organisations/{$organisation->uid}/terms"));
        $this->get("/o/{$organisation->uid}")->assertOk()->assertSee('TTV Alumni: Terms and conditions');
    }
}

