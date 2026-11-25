<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The header's organisation switcher (user-interface shows the services tagged
 * "header-widgets"): the current organisation, and a menu to switch when there
 * is more than one. Nothing for users in no organisation.
 */
class OrganisationSwitcher
{
    public function __construct(private OrganisationContext $context) {}

    public function render(?Authenticatable $user): ?string
    {
        if (! $user) {
            return null;
        }

        $available = $this->context->available($user);
        if ($available->isEmpty()) {
            return null;
        }

        return view('organisation::context.switcher', [
            'current' => $this->context->current($user),
            'available' => $available,
            'canClear' => OrganisationAccess::isPlatformAdmin($user),
        ])->render();
    }
}
