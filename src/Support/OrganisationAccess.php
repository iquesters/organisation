<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Iquesters\Organisation\Models\Organisation;

/**
 * Who may see and administer an organisation. Community, event and the other
 * modules ask here, so every module gives the same answer.
 *
 * - super-admin: every organisation;
 * - org-admin: only the organisations where their role is org-admin
 *   (organisation_user_roles). Being org-admin of one organisation gives
 *   nothing in another: there the person is only a member until their role
 *   there is changed. The global "admin" role grants no organisation;
 * - member: belongs to the organisation (model_has_organisations); can view.
 *
 * Creating and deleting organisations stays super-admin only.
 */
class OrganisationAccess
{
    /** The global role that opens the admin menus (see OrganisationRoles::MENU_ROLE). */
    public const ADMIN_ROLE = OrganisationRoles::MENU_ROLE;

    private static bool $warned = false;

    public static function isPlatformAdmin(?Authenticatable $user): bool
    {
        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('super-admin', 'web');
    }

    /** @return int[] ids of the active organisations the user administers */
    public static function adminOrganisationIds(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        if (self::isPlatformAdmin($user)) {
            return Organisation::query()->where('status', 'active')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if (! OrganisationRoles::available()) {
            // Not migrated yet: nobody but super-admins administers (fail closed).
            if (! self::$warned) {
                self::$warned = true;
                Log::warning('organisation.roles_missing', ['reason' => 'organisation_user_roles is missing: run the migrations']);
            }

            return [];
        }

        return OrganisationRoles::adminOrganisationIds($user);
    }

    /** @return int[] ids of the active organisations the user belongs to */
    public static function memberOrganisationIds(?Authenticatable $user): array
    {
        if (! $user || ! method_exists($user, 'organisations')) {
            return [];
        }

        return $user->organisations()->where('organisations.status', 'active')->pluck('organisations.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function canAdminister(?Authenticatable $user, Organisation $organisation): bool
    {
        return in_array((int) $organisation->id, self::adminOrganisationIds($user), true);
    }

    public static function canView(?Authenticatable $user, Organisation $organisation): bool
    {
        return self::isPlatformAdmin($user)
            || in_array((int) $organisation->id, self::memberOrganisationIds($user), true)
            || self::canAdminister($user, $organisation);
    }
}
