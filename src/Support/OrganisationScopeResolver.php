<?php

namespace Iquesters\Organisation\Support;

use Iquesters\Foundation\Contracts\EntityScopeResolver;
use Iquesters\Organisation\Models\Organisation;

/**
 * Which organisation_id values the current user may see and write through
 * foundation's entity API (EntityScope fails closed without a resolver).
 *
 * - super-admin (web guard): every organisation;
 * - anyone else: the organisations they are a member of;
 * - no user: none.
 */
class OrganisationScopeResolver implements EntityScopeResolver
{
    public const COLUMN = 'organisation_id';

    public function resolveAllowedIds(string $column): ?array
    {
        if ($column !== self::COLUMN) {
            return null;
        }

        $user = auth()->user();
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin', 'web')) {
            return Organisation::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if (! method_exists($user, 'organisations')) {
            return [];
        }

        return $user->organisations()->pluck('organisations.id')->map(fn ($id) => (int) $id)->all();
    }
}
