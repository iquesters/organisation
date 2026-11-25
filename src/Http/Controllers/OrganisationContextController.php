<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationContext;

/** Switch the organisation the user is working in (the header's switcher). */
class OrganisationContextController extends Controller
{
    public function __construct(private OrganisationContext $context) {}

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['organisation_uid' => ['required', 'string', 'max:26']]);

        if (! $this->context->set($request->user(), $validated['organisation_uid'])) {
            return back()->with('error', "You can't work in that organisation.");
        }

        return back()->with('success', 'Now working in '.$this->context->current($request->user())?->name.'.');
    }

    /** No organisation: super-admins see all of them. */
    public function destroy(): RedirectResponse
    {
        $this->context->clear();

        return back();
    }

    /** "My organisation": the current organisation's profile, else the list to pick one. */
    public function mine(Request $request): RedirectResponse
    {
        $current = $this->context->current($request->user());

        if ($current) {
            return redirect()->route('organisations.profile.show', $current->uid);
        }

        // No organisation: super-admins get the list; others the dashboard, saying why.
        return OrganisationAccess::isPlatformAdmin($request->user())
            ? redirect()->route('organisations.index')
            : redirect()->to(\Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : '/')->with('error', "You don't belong to an organisation yet.");
    }
}
