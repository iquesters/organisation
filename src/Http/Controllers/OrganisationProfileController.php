<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationPolicies;
use Iquesters\Organisation\Support\OrganisationProfile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An organisation's profile: members see it, its admins edit it, and the
 * public page (/o/{uid}) shows it to everyone when switched on.
 */
class OrganisationProfileController extends Controller
{
    use Loggable;

    public function show(Request $request, string $organisationUid): View
    {
        $organisation = $this->find($organisationUid);
        abort_unless(OrganisationAccess::canView($request->user(), $organisation), 404);

        return view('organisation::profile.show', [
            'organisation' => $organisation->load('officeBearers'),
            'values' => $organisation->profileValues(),
            // The policies have their own pages (/organisations/{uid}/terms, /privacy).
            'fields' => collect(OrganisationProfile::fields())->reject(fn ($f) => ($f['page'] ?? true) === false)->values()->all(),
            'groups' => OrganisationProfile::groups(),
            'canEdit' => OrganisationAccess::canAdminister($request->user(), $organisation),
            'isPublic' => OrganisationProfile::isPublic($organisation),
            // The public page's full address (copy link), and the Terms / Privacy links.
            'publicUrl' => route('organisations.public', $organisation->uid),
            'policies' => collect(OrganisationPolicies::KINDS)->map(fn ($meta, $kind) => [
                'label' => $meta['label'],
                'url' => ($url = OrganisationPolicies::url($organisation, $kind)) ? url($url) : null,
                'own' => trim((string) $organisation->getMetaValue($kind.'_url')) !== '' || OrganisationPolicies::text($organisation, $kind) !== '',
            ])->filter(fn ($p) => $p['url'])->values()->all(),
            'policiesVersion' => OrganisationPolicies::version($organisation),
            // The member directory lives in the community module, when installed.
            'membersUrl' => Route::has('community.membership') ? route('community.membership', ['organisation' => $organisation->uid]) : null,
        ]);
    }

    public function edit(Request $request, string $organisationUid): View
    {
        $organisation = $this->findForAdmin($request, $organisationUid);

        $values = $organisation->profileValues();

        return view('organisation::profile.edit', [
            'organisation' => $organisation,
            // The form's values: the details, the office bearers (repeater rows) and the switches.
            'formData' => collect(OrganisationProfile::fields())->mapWithKeys(fn ($f) => [$f['id'] => $values[$f['id']] ?? ''])->all() + [
                'office_bearers' => $organisation->officeBearers()->get()->map->only(['id', 'title', 'name', 'email', 'phone', 'is_public'])->all(),
                'public_page' => ($values[OrganisationProfile::PUBLIC_PAGE] ?? '0') === '1',
                'remove_logo' => false,
            ],
            'hasLogo' => ! empty($values[OrganisationProfile::LOGO]),
        ]);
    }

    /** The profile form (form schema organisation-profile) posts here. */
    public function update(Request $request, string $organisationUid): RedirectResponse|JsonResponse
    {
        $organisation = $this->findForAdmin($request, $organisationUid);

        $rules = [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'public_page' => ['nullable', 'boolean'],
            'office_bearers' => ['nullable', 'array', 'max:30'],
            'office_bearers.*.title' => ['nullable', 'string', 'max:100'],
            'office_bearers.*.name' => ['nullable', 'string', 'max:255'],
            'office_bearers.*.email' => ['nullable', 'email', 'max:255'],
            'office_bearers.*.phone' => ['nullable', 'string', 'max:32'],
            'office_bearers.*.is_public' => ['nullable', 'boolean'],
        ];
        $attributes = [];
        foreach (OrganisationProfile::fields() as $field) {
            $rules[$field['id']] = $field['rules'];
            $attributes[$field['id']] = $field['label'];
        }
        $validated = $request->validate($rules, [], $attributes);

        $userId = (int) $request->user()->id;
        OrganisationProfile::save(
            $organisation,
            collect(OrganisationProfile::fields())->mapWithKeys(fn ($f) => [$f['id'] => $validated[$f['id']] ?? null])->all(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
            $request->boolean('public_page'),
            $userId
        );
        OrganisationProfile::saveOfficeBearers($organisation, (array) ($validated['office_bearers'] ?? []));

        static::logInfo('Organisation profile updated', ['organisation' => $organisation->uid, 'user_id' => $userId]);

        $url = route('organisations.profile.show', $organisation->uid);
        if ($request->expectsJson()) {
            session()->flash('success', 'Profile saved.');

            return response()->json(['success' => true, 'message' => 'Profile saved.', 'redirect_url' => $url]);
        }

        return redirect($url)->with('success', 'Profile saved.');
    }

    /** The logo: for those who may see the profile, or anyone when the public page is on. */
    public function logo(Request $request, string $organisationUid): StreamedResponse
    {
        $organisation = $this->find($organisationUid);
        abort_unless(OrganisationProfile::isPublic($organisation) || OrganisationAccess::canView($request->user(), $organisation), 404);

        $path = $organisation->getMetaValue(OrganisationProfile::LOGO);
        $disk = Storage::disk(OrganisationProfile::LOGO_DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=3600']);
    }

    /** /o/{uid}: no sign-in. Contact details of office bearers are never shown here. */
    public function publicShow(string $key): View|\Illuminate\Http\RedirectResponse
    {
        $organisation = Organisation::query()->where('uid', $key)->first();
        if (! $organisation) {
            // An address shared before (by name): the same page at its lasting address.
            $old = Organisation::query()->where('slug', $key)->firstOrFail();
            abort_unless(OrganisationProfile::isPublic($old), 404);

            return redirect()->route('organisations.public', $old->uid, 301);
        }
        abort_unless(OrganisationProfile::isPublic($organisation), 404);
        // Signing up from here: the membership page has this organisation chosen.
        app(\Iquesters\Organisation\Support\OrganisationContext::class)->rememberVisit($organisation);

        return view('organisation::profile.public', [
            'organisation' => $organisation,
            'values' => $organisation->profileValues(),
            'fields' => collect(OrganisationProfile::fields())->where('public', true)->values()->all(),
            'groups' => OrganisationProfile::groups(),
            'officeBearers' => $organisation->officeBearers()->where('is_public', true)->get(),
            // Applicants read them before joining.
            'policyLinks' => OrganisationPolicies::links($organisation),
        ]);
    }

    /** The organisation's own Terms / Privacy text (public: applicants read it before joining). */
    public function policy(string $organisationUid, string $kind): View|\Illuminate\Http\RedirectResponse
    {
        abort_unless(array_key_exists($kind, OrganisationPolicies::KINDS), 404);
        $organisation = $this->find($organisationUid);
        $text = OrganisationPolicies::text($organisation, $kind);

        if ($text === '') {
            // A link, or the platform's page.
            $url = OrganisationPolicies::url($organisation, $kind);
            abort_unless($url, 404);

            return redirect()->to($url);
        }

        return view('organisation::profile.policy', [
            'organisation' => $organisation,
            'title' => OrganisationPolicies::KINDS[$kind]['label'],
            'text' => $text,
            'version' => OrganisationPolicies::version($organisation),
        ]);
    }

    private function find(string $uid): Organisation
    {
        return Organisation::query()->where('uid', $uid)->firstOrFail();
    }

    /** Another organisation's admin gets 403 (they know it exists from the list). */
    private function findForAdmin(Request $request, string $uid): Organisation
    {
        $organisation = $this->find($uid);
        abort_unless(OrganisationAccess::canAdminister($request->user(), $organisation), 403);

        return $organisation;
    }
}
