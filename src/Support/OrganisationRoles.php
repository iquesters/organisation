<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Models\OrganisationUserRole;
use Spatie\Permission\Models\Role;

/**
 * A user's place in an organisation: membership (model_has_organisations) and
 * their role there (organisation_user_roles: member or org-admin), kept together.
 *
 * The global "admin" role only opens the admin menus and permissions
 * (Manage events, Applications…); which organisations someone administers
 * comes from organisation_user_roles. The global role follows: a user holds it
 * while they are org-admin of at least one organisation.
 */
class OrganisationRoles
{
    /** The global role that opens the admin menus (modules give their permissions to it). */
    public const MENU_ROLE = 'admin';

    public static function available(): bool
    {
        return Schema::hasTable('organisation_user_roles');
    }

    /** Add the user to the organisation (as member unless a role is given); keeps an existing role when none is given. */
    public static function join(Authenticatable $user, Organisation $organisation, ?string $role = null, ?int $by = null): OrganisationUserRole
    {
        return DB::transaction(function () use ($user, $organisation, $role, $by) {
            if (method_exists($user, 'organisations')) {
                $user->organisations()->syncWithoutDetaching([$organisation->id]);
            }

            $row = OrganisationUserRole::query()->firstOrNew([
                'organisation_id' => $organisation->id,
                'user_id' => $user->getAuthIdentifier(),
                'user_type' => self::type($user),
            ]);
            if (! $row->exists) {
                $row->role = $role ?? OrganisationUserRole::ROLE_MEMBER;
                $row->created_by = $by;
            } elseif ($role !== null) {
                $row->role = $role;
            }
            $row->status = 'active';
            $row->updated_by = $by;
            $row->save();

            self::syncMenuRole($user);

            return $row;
        });
    }

    /** Change the user's role in an organisation they belong to. */
    public static function setRole(Authenticatable $user, Organisation $organisation, string $role, ?int $by = null): OrganisationUserRole
    {
        if (! in_array($role, OrganisationUserRole::ROLES, true)) {
            throw new \InvalidArgumentException("Unknown organisation role: {$role}");
        }

        return self::join($user, $organisation, $role, $by);
    }

    /** Take the user out of the organisation (membership and role). */
    public static function leave(Authenticatable $user, Organisation $organisation): void
    {
        DB::transaction(function () use ($user, $organisation) {
            if (method_exists($user, 'organisations')) {
                $user->organisations()->detach($organisation->id);
            }
            OrganisationUserRole::query()
                ->where('organisation_id', $organisation->id)
                ->where('user_id', $user->getAuthIdentifier())
                ->where('user_type', self::type($user))
                ->delete();

            self::syncMenuRole($user);
        });
    }

    public static function roleIn(?Authenticatable $user, Organisation $organisation): ?string
    {
        if (! $user || ! self::available()) {
            return null;
        }

        return OrganisationUserRole::query()
            ->where('organisation_id', $organisation->id)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('user_type', self::type($user))
            ->where('status', 'active')
            ->value('role');
    }

    /** @return int[] active organisations where the user is org-admin */
    public static function adminOrganisationIds(Authenticatable $user): array
    {
        if (! self::available()) {
            return [];
        }

        return OrganisationUserRole::query()
            ->join('organisations', 'organisations.id', '=', 'organisation_user_roles.organisation_id')
            ->where('organisation_user_roles.user_id', $user->getAuthIdentifier())
            ->where('organisation_user_roles.user_type', self::type($user))
            ->where('organisation_user_roles.role', OrganisationUserRole::ROLE_ORG_ADMIN)
            ->where('organisation_user_roles.status', 'active')
            ->where('organisations.status', 'active')
            ->pluck('organisation_user_roles.organisation_id')
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();
    }

    /** The global menu role follows the per-organisation roles (super-admins are left alone). */
    public static function syncMenuRole(Authenticatable $user): void
    {
        if (! method_exists($user, 'hasRole') || $user->hasRole('super-admin', 'web')) {
            return;
        }

        $isOrgAdmin = OrganisationUserRole::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('user_type', self::type($user))
            ->where('role', OrganisationUserRole::ROLE_ORG_ADMIN)
            ->where('status', 'active')
            ->exists();

        if ($isOrgAdmin && ! $user->hasRole(self::MENU_ROLE, 'web')) {
            $user->assignRole(Role::findOrCreate(self::MENU_ROLE, 'web'));
        } elseif (! $isOrgAdmin && $user->hasRole(self::MENU_ROLE, 'web')) {
            $user->removeRole(self::MENU_ROLE);
        }
    }

    /**
     * Memberships of users that have no role row yet (made by code that
     * attached the user directly) get one as member.
     *
     * @param  int[]  $organisationIds
     */
    public static function heal(array $organisationIds): void
    {
        if (! self::available() || $organisationIds === []) {
            return;
        }

        $userClass = \Iquesters\Foundation\Support\UserModel::name();
        $types = array_values(array_unique([$userClass, (new $userClass)->getMorphClass()]));

        DB::table('model_has_organisations as m')
            ->leftJoin('organisation_user_roles as r', function ($join) {
                $join->on('r.organisation_id', '=', 'm.organisation_id')
                    ->on('r.user_id', '=', 'm.model_id')
                    ->on('r.user_type', '=', 'm.model_type');
            })
            ->whereIn('m.organisation_id', $organisationIds)
            ->whereIn('m.model_type', $types)
            ->whereNull('r.id')
            ->get(['m.organisation_id', 'm.model_id', 'm.model_type'])
            ->each(fn ($link) => OrganisationUserRole::query()->create([
                'organisation_id' => $link->organisation_id,
                'user_id' => $link->model_id,
                'user_type' => $link->model_type,
                'role' => OrganisationUserRole::ROLE_MEMBER,
            ]));
    }

    /** The user's morph class, as model_has_organisations stores it. */
    public static function type(Authenticatable $user): string
    {
        return method_exists($user, 'getMorphClass') ? $user->getMorphClass() : get_class($user);
    }
}
