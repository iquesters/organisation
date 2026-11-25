<?php

namespace Iquesters\Organisation\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationProfile;
use Iquesters\Organisation\Tests\TestCase;

class OrganisationProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(OrganisationProfile::LOGO_DISK);
    }

    private function member(Organisation $organisation, string $role = 'user')
    {
        $user = $this->makeUser($role);
        // An "admin" member is org-admin there (per-organisation role); anyone else a member.
        \Iquesters\Organisation\Support\OrganisationRoles::join($user, $organisation, $role === 'admin' ? 'org-admin' : 'member');

        return $user;
    }

    private function profile(array $profile = [], array $extra = []): array
    {
        // As the profile form (form schema) sends it: the details as top-level fields.
        return array_merge($profile + [
            'tagline' => 'Old students of TTV',
            'about' => 'Founded by the class of 1950.',
            'email' => 'office@ttvians.test',
            'website' => 'https://ttvians.test',
            'affiliation' => 'WBBSE',
        ], [
            'public_page' => '1',
            'office_bearers' => [
                ['title' => 'President', 'name' => 'Ann Roy', 'email' => 'ann@ttvians.test', 'is_public' => '1'],
                ['title' => 'Treasurer', 'name' => 'Bob Sen', 'email' => 'bob@ttvians.test'],
                ['title' => '', 'name' => ''],
            ],
        ], $extra);
    }

    public function test_members_see_the_profile_and_others_do_not(): void
    {
        $organisation = $this->makeOrganisation('TTVians');

        $this->actingAs($this->member($organisation))->get("/organisations/{$organisation->uid}/profile")
            ->assertOk()->assertSee('TTVians')->assertDontSee('Edit profile');
        $this->actingAs($this->makeUser('user'))->get("/organisations/{$organisation->uid}/profile")->assertNotFound();
        $this->actingAs($this->makeUser('super-admin'))->get("/organisations/{$organisation->uid}/profile")
            ->assertOk()->assertSee('Edit profile');
    }

    public function test_an_organisation_admin_edits_their_profile(): void
    {
        $organisation = $this->makeOrganisation('TTVians Alumni');
        $admin = $this->member($organisation, 'admin');
        $this->assertTrue(OrganisationAccess::canAdminister($admin, $organisation));

        $this->actingAs($admin)->get("/organisations/{$organisation->uid}/profile/edit")->assertOk();
        $this->put("/organisations/{$organisation->uid}/profile", $this->profile())
            ->assertRedirect("/organisations/{$organisation->uid}/profile");

        $organisation->refresh();
        $this->assertSame('ttvians-alumni', $organisation->slug);
        $this->assertSame('WBBSE', $organisation->getMetaValue('affiliation'));
        $this->assertSame('1', $organisation->getMetaValue(OrganisationProfile::PUBLIC_PAGE));
        $this->assertSame(['President', 'Treasurer'], $organisation->officeBearers->pluck('title')->all());
        $this->assertSame([true, false], $organisation->officeBearers->pluck('is_public')->all());

        // A blank field removes the value.
        $this->put("/organisations/{$organisation->uid}/profile", $this->profile(['affiliation' => '']));
        $this->assertNull($organisation->getMetaValue('affiliation'));
    }

    public function test_only_its_admins_edit_a_profile(): void
    {
        $mine = $this->makeOrganisation('Alpha');
        $other = $this->makeOrganisation('Beta');
        $admin = $this->member($mine, 'admin');

        $this->actingAs($admin)->put("/organisations/{$other->uid}/profile", $this->profile())->assertForbidden();
        $this->actingAs($this->member($mine))->get("/organisations/{$mine->uid}/profile/edit")->assertForbidden();
        $this->assertNull($other->fresh()->slug);
    }

    public function test_the_profile_is_checked(): void
    {
        $organisation = $this->makeOrganisation();
        $this->actingAs($this->makeUser('super-admin'))
            ->put("/organisations/{$organisation->uid}/profile", $this->profile(['website' => 'not a url', 'founded_year' => '1500']))
            ->assertSessionHasErrors(['website', 'founded_year']);
    }

    public function test_the_public_page_shows_only_public_details(): void
    {
        $organisation = $this->makeOrganisation('TTVians');
        $this->actingAs($this->makeUser('super-admin'))->put("/organisations/{$organisation->uid}/profile", $this->profile());
        auth()->logout();

        $this->get('/o/ttvians')->assertOk()
            ->assertSee('Old students of TTV')
            ->assertSee('Ann Roy')
            ->assertDontSee('Bob Sen')            // not on the public page
            ->assertDontSee('ann@ttvians.test');  // office bearers' contact details stay private

        $organisation->setMetaValue(OrganisationProfile::PUBLIC_PAGE, '0');
        $this->get('/o/ttvians')->assertNotFound();
    }

    public function test_the_logo_is_stored_privately_and_served(): void
    {
        $organisation = $this->makeOrganisation('TTVians');
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->put("/organisations/{$organisation->uid}/profile", $this->profile([], [
            'public_page' => '0',
            'logo' => UploadedFile::fake()->image('logo.png', 120, 120),
        ]));
        $path = $organisation->getMetaValue(OrganisationProfile::LOGO);
        Storage::disk(OrganisationProfile::LOGO_DISK)->assertExists($path);
        $this->get("/organisations/{$organisation->uid}/logo")->assertOk();

        // Not public: strangers don't get it.
        auth()->logout();
        $this->get("/organisations/{$organisation->uid}/logo")->assertNotFound();

        $this->actingAs($admin)->put("/organisations/{$organisation->uid}/profile", $this->profile([], ['remove_logo' => '1']));
        Storage::disk(OrganisationProfile::LOGO_DISK)->assertMissing($path);
        $this->assertNull($organisation->getMetaValue(OrganisationProfile::LOGO));
    }

    public function test_slugs_stay_unique(): void
    {
        $a = $this->makeOrganisation('TTVians');
        $b = $this->makeOrganisation('TTVians');
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->put("/organisations/{$a->uid}/profile", $this->profile());
        $this->put("/organisations/{$b->uid}/profile", $this->profile());

        $this->assertSame(['ttvians', 'ttvians-2'], [$a->fresh()->slug, $b->fresh()->slug]);
    }
}
