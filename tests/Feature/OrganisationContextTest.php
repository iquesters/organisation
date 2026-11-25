<?php

namespace Iquesters\Organisation\Tests\Feature;

use Iquesters\Organisation\Support\OrganisationContext;
use Iquesters\Organisation\Support\OrganisationSwitcher;
use Iquesters\Organisation\Tests\TestCase;

class OrganisationContextTest extends TestCase
{
    private function context(): OrganisationContext
    {
        return app(OrganisationContext::class);
    }

    public function test_someone_in_one_organisation_is_always_in_it(): void
    {
        $organisation = $this->makeOrganisation('TTVians');
        $user = $this->makeUser('user');
        $user->organisations()->attach($organisation->id);

        $this->assertSame($organisation->id, $this->context()->id($user));
        $this->assertNull($this->context()->id($this->makeUser('user')));
    }

    public function test_switching_only_to_organisations_the_user_may_see(): void
    {
        $a = $this->makeOrganisation('Alpha');
        $b = $this->makeOrganisation('Beta');
        $c = $this->makeOrganisation('Gamma');
        $user = $this->makeUser('user');
        $user->organisations()->attach([$a->id, $b->id]);
        $this->actingAs($user);

        // Two organisations: none until they choose.
        $this->assertNull($this->context()->current());

        $this->from('/dashboard')->post('/organisations/context', ['organisation_uid' => $b->uid])
            ->assertRedirect('/dashboard')->assertSessionHas('success', 'Now working in Beta.');
        $this->assertSame($b->id, app(OrganisationContext::class)->id());

        $this->post('/organisations/context', ['organisation_uid' => $c->uid])->assertSessionHas('error');
        $this->assertSame($b->uid, session(OrganisationContext::SESSION_KEY));

        // Removed from Beta: the stale choice is ignored, and with Alpha their only
        // organisation they are in it.
        $user->organisations()->detach($b->id);
        $this->app->forgetScopedInstances();
        $this->assertSame($a->id, app(OrganisationContext::class)->id());
    }

    public function test_super_admins_may_work_in_all_organisations(): void
    {
        $a = $this->makeOrganisation('Alpha');
        $this->actingAs($this->makeUser('super-admin'));

        $this->post('/organisations/context', ['organisation_uid' => $a->uid]);
        $this->assertSame($a->id, app(OrganisationContext::class)->id());

        $this->delete('/organisations/context');
        $this->app->forgetScopedInstances();
        $this->assertNull(app(OrganisationContext::class)->current());
    }

    public function test_my_organisation_opens_the_current_profile(): void
    {
        $organisation = $this->makeOrganisation('TTVians');
        $user = $this->makeUser('user');
        $user->organisations()->attach($organisation->id);

        $this->actingAs($user)->get('/organisations/mine')->assertRedirect("/organisations/{$organisation->uid}/profile");
        // No organisation: the dashboard (here '/', user-management isn't installed), not the super-admins' list.
        $this->actingAs($this->makeUser('user'))->get('/organisations/mine')->assertRedirect('/')->assertSessionHas('error');
        $this->actingAs($this->makeUser('super-admin'))->get('/organisations/mine')->assertRedirect('/organisations');
    }

    public function test_the_header_switcher(): void
    {
        $a = $this->makeOrganisation('Alpha');
        $b = $this->makeOrganisation('Beta');
        $user = $this->makeUser('user');
        $switcher = app(OrganisationSwitcher::class);

        $this->assertNull($switcher->render($user));  // in no organisation

        $user->organisations()->attach($a->id);
        $this->assertStringContainsString('Alpha', (string) $switcher->render($user));

        $user->organisations()->attach($b->id);
        $html = (string) $switcher->render($user);
        $this->assertStringContainsString('All organisations', $html); // nothing chosen yet
        $this->assertStringContainsString('Beta', $html);

        $this->assertContains(OrganisationSwitcher::class, array_map('get_class', iterator_to_array(app()->tagged('header-widgets'))));
    }
}
