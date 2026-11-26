<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Iquesters\Organisation\Models\Organisation;

/**
 * The organisation the user is working in ("context"), kept in the session.
 *
 * - Only an organisation the user may see can be the context (member, its
 *   admin, or super-admin); a stale one in the session is ignored.
 * - Someone who belongs to exactly one organisation is always in it.
 * - Super-admins may also have no context: they then see all organisations.
 *
 * Other modules read it to default to that organisation (e.g. community's
 * apply page, approval queue and bulk upload).
 */
class OrganisationContext
{
    public const SESSION_KEY = 'organisation.context_uid';

    /** The organisation whose public page (or public event) a visitor came through, before signing in. */
    public const CAME_FROM_KEY = 'organisation.came_from_uid';

    /** Organisations the user may switch to: active ones they belong to or administer (all, for super-admin). */
    public function available(?Authenticatable $user = null): Collection
    {
        $user ??= auth()->user();
        if (! $user) {
            return collect();
        }

        $ids = OrganisationAccess::isPlatformAdmin($user)
            ? null
            : array_unique([...OrganisationAccess::memberOrganisationIds($user), ...OrganisationAccess::adminOrganisationIds($user)]);

        return Organisation::query()
            ->where('status', 'active')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')
            ->get(['id', 'uid', 'name', 'slug']);
    }

    public function current(?Authenticatable $user = null): ?Organisation
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        $available = $this->available($user);
        $chosen = session(self::SESSION_KEY);
        $current = ($chosen ? $available->firstWhere('uid', $chosen) : null)
            ?? ($available->count() === 1 && ! OrganisationAccess::isPlatformAdmin($user) ? $available->first() : null);

        return $current;
    }

    public function id(?Authenticatable $user = null): ?int
    {
        $current = $this->current($user);

        return $current ? (int) $current->id : null;
    }

    /** @return bool false when the user may not work in that organisation */
    public function set(Authenticatable $user, string $organisationUid): bool
    {
        if (! $this->available($user)->contains('uid', $organisationUid)) {
            return false;
        }

        session([self::SESSION_KEY => $organisationUid]);

        return true;
    }

    /** A visitor reached the organisation's public page or one of its public events: remember it (no sign-in needed). */
    public function rememberVisit(Organisation $organisation): void
    {
        session([self::CAME_FROM_KEY => $organisation->uid]);
    }

    /**
     * The organisation the person came through (see rememberVisit), if it's
     * active. Other modules use it to preselect it (e.g. community's
     * membership page after signing up from an event link).
     */
    public function cameFrom(): ?Organisation
    {
        $uid = session(self::CAME_FROM_KEY);

        return $uid ? Organisation::query()->where('uid', $uid)->where('status', 'active')->first() : null;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
