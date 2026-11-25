<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Iquesters\Organisation\Models\Organisation;

/**
 * Who may see and edit an organisation's profile.
 *
 * - super-admin: every organisation;
 * - organisation admin: Track B's TenantAuthorization (explicit
 *   organisation_role_assignments) when that table exists; until then, a user
 *   with the "admin" role administers the organisations they belong to
 *   (logged once as a warning). The strict rule applies as soon as the table exists.
 * - member: belongs to the organisation (model_has_organisations); can view.
 *
 * Creating and deleting organisations stays super-admin only (ensure.admin).
 */
class OrganisationAccess
{
    public const ASSIGNMENTS_TABLE = 'organisation_role_assignments';

    public const TENANT_POLICY = 'Iquesters\\Foundation\\Security\\TenantAuthorization';

    public const ADMIN_ROLE = 'admin';

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

        if (class_exists(self::TENANT_POLICY) && Schema::hasTable(self::ASSIGNMENTS_TABLE)) {
            return array_map('intval', app(self::TENANT_POLICY)->adminOrganisationIds($user));
        }

        if (! method_exists($user, 'hasRole') || ! $user->hasRole(self::ADMIN_ROLE, 'web') || ! method_exists($user, 'organisations')) {
            return [];
        }

        if (! self::$warned) {
            self::$warned = true;
            Log::warning('organisation.admin_fallback', [
                'reason' => self::ASSIGNMENTS_TABLE.' is missing: "admin" members of an organisation administer it',
            ]);
        }

        return self::memberOrganisationIds($user);
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
