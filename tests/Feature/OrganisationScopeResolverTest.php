<?php

namespace Iquesters\Organisation\Tests\Feature;

use Iquesters\Foundation\Services\Entity\EntityScope;
use Iquesters\Organisation\Support\OrganisationScopeResolver;
use Iquesters\Organisation\Tests\TestCase;

class OrganisationScopeResolverTest extends TestCase
{
    public function test_it_is_registered_for_the_entity_api(): void
    {
        $this->assertContains(
            OrganisationScopeResolver::class,
            array_map('get_class', iterator_to_array(app()->tagged('entity-scope-resolvers')))
        );
    }

    public function test_super_admins_see_every_organisation_members_only_their_own(): void
    {
        $a = $this->makeOrganisation('A');
        $b = $this->makeOrganisation('B');
        $scope = app(EntityScope::class);

        $this->actingAs($this->makeUser('super-admin'));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $scope->allowedIds('organisation_id'));

        $member = $this->makeUser('user');
        $member->organisations()->attach($b->id);
        $this->actingAs($member);
        $this->assertSame([$b->id], $scope->allowedIds('organisation_id'));

        $this->actingAs($this->makeUser('admin'));
        $this->assertSame([], $scope->allowedIds('organisation_id'));
    }

    public function test_it_has_no_opinion_on_other_columns(): void
    {
        $this->assertNull(app(OrganisationScopeResolver::class)->resolveAllowedIds('team_id'));
    }
}
