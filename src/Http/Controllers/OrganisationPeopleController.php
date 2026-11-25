<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Iquesters\Foundation\Support\UserModel;
use Iquesters\Foundation\System\Http\ApiResponse;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Models\OrganisationUserRole;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationForms;
use Iquesters\Organisation\Support\OrganisationRoles;
use Spatie\Permission\Models\Role;

/**
 * The people of an organisation and their role there (member / org-admin):
 * the generic table (organisation-people-table, ?organisation={uid}) and the
 * organisation-person-* form schemas.
 *
 * Super-admins manage every organisation; an org-admin manages only the
 * organisations where they are org-admin: add people (an existing account by
 * email, or a new account), change their role, take them out. Deleting an
 * account stays with user management (super-admins): it may belong to other
 * organisations too.
 */
class OrganisationPeopleController extends Controller
{
    use Loggable;

    public const TABLE = 'organisation-people-table';

    /** Sidebar "People and roles": every organisation the user manages (the organisation they work in, if chosen). */
    public function all(Request $request): RedirectResponse
    {
        abort_unless(OrganisationAccess::adminOrganisationIds($request->user()) !== [] || OrganisationAccess::isPlatformAdmin($request->user()), 403);
        $context = app(\Iquesters\Organisation\Support\OrganisationContext::class)->current();
        $filter = $context && OrganisationAccess::canAdminister($request->user(), $context) ? '?organisation='.$context->uid : '';

        return redirect()->to(OrganisationController::tableUrl(self::TABLE).$filter);
    }

    /** Old Users tab: the table filtered to the organisation. */
    public function index(Request $request, string $organisationUid): RedirectResponse
    {
        $organisation = $this->manageable($request, $organisationUid);

        return redirect()->to(OrganisationController::tableUrl(self::TABLE).'?organisation='.$organisation->uid);
    }

    /** Old "new user" page: the add form. */
    public function create(Request $request, string $organisationUid): RedirectResponse
    {
        $organisation = $this->manageable($request, $organisationUid);
        $form = DB::table('form_schemas')->where('slug', OrganisationForms::PERSON_ADD)->value('uid');

        return redirect()->to(OrganisationController::tableUrl(self::TABLE).($form ? "/create/{$form}" : '').'?organisation='.$organisation->uid);
    }

    /** The table's rows (data_url); ?organisation={uid} limits them to one organisation. */
    public function data(Request $request): JsonResponse
    {
        $ids = OrganisationAccess::adminOrganisationIds($request->user());
        if ($uid = $request->query('organisation')) {
            $id = Organisation::query()->where('uid', $uid)->value('id');
            $ids = in_array((int) $id, $ids, true) ? [(int) $id] : [];
        }
        OrganisationRoles::heal($ids);

        $query = OrganisationUserRole::query()->with(['organisation:id,uid,name', 'user:id,uid,name,email'])->whereIn('organisation_id', $ids);
        if (in_array($request->query('role'), OrganisationUserRole::ROLES, true)) {
            $query->where('role', $request->query('role'));
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->whereHas('user', fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        $offset = max(0, (int) $request->query('offset', 0));
        $length = min(100, max(1, (int) $request->query('length', 25)));
        $total = (clone $query)->count();
        $rows = $query->orderByDesc('role')->orderBy('id')->skip($offset)->take($length)->get()->map(fn ($r) => $this->row($r, $request))->all();

        return ApiResponse::success($rows, 'OK', 200, ['pagination' => ['total' => $total, 'offset' => $offset, 'length' => $length]]);
    }

    public function record(Request $request, string $uid): JsonResponse
    {
        return ApiResponse::success($this->row($this->findRow($request, $uid), $request));
    }

    /** Record section: who the person is (and, for your own row, why you can't change it). */
    public function summary(Request $request, string $uid): \Illuminate\View\View
    {
        $row = $this->findRow($request, $uid);

        return view('organisation::people.summary', ['person' => $this->row($row, $request)]);
    }

    /**
     * "Existing account" options: super-admins pick from everyone who signed up;
     * an org-admin types the email (they can't browse every account on the platform).
     */
    public function userOptions(Request $request): JsonResponse
    {
        if (! OrganisationAccess::isPlatformAdmin($request->user())) {
            return response()->json(['success' => true, 'data' => [['value' => '', 'label' => 'Type their email below']]]);
        }

        $users = UserModel::query()->orderBy('name')->limit(500)->get(['uid', 'name', 'email']);

        return response()->json(['success' => true, 'data' => [
            ['value' => '', 'label' => 'Choose… (or type the email below)'],
            ...$users->map(fn ($u) => ['value' => $u->uid, 'label' => $u->name.' ('.$u->email.')'])->all(),
        ]]);
    }

    public function organisationOptions(Request $request): JsonResponse
    {
        $organisations = Organisation::query()->whereIn('id', OrganisationAccess::adminOrganisationIds($request->user()))->orderBy('name')->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => [
            ['value' => '', 'label' => 'Choose…'],
            ...$organisations->map(fn ($o) => ['value' => (int) $o->id, 'label' => $o->name])->all(),
        ]]);
    }

    /** Add someone by email; a new account when nobody has that email yet. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organisation_id' => ['required', 'integer', Rule::in(OrganisationAccess::adminOrganisationIds($request->user()))],
            'role' => ['required', Rule::in(OrganisationUserRole::ROLES)],
            // An existing account (super-admins choose it), or an email.
            'user_uid' => ['nullable', 'string', 'max:26'],
            'email' => ['required_without:user_uid', 'nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ], [
            'organisation_id.in' => "You don't manage this organisation.",
            'email.required_without' => 'Choose an existing account or type their email.',
        ]);

        $organisation = Organisation::query()->findOrFail($data['organisation_id']);
        $chosen = ! empty($data['user_uid']) && OrganisationAccess::isPlatformAdmin($request->user())
            ? UserModel::query()->where('uid', $data['user_uid'])->first()
            : null;
        $email = $chosen ? strtolower((string) $chosen->email) : strtolower(trim((string) ($data['email'] ?? '')));
        $user = $chosen ?? UserModel::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $created = false;

        if (! $user) {
            if (trim((string) ($data['name'] ?? '')) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Nobody has signed up with this email yet: enter their name to make an account.',
                    'errors' => ['name' => ['Enter the name for the new account.']],
                ], 422);
            }
            $user = UserModel::query()->create([
                'uid' => (string) Str::ulid(),
                'name' => trim($data['name']),
                'email' => $email,
                'status' => 'active',
                // Unknown to anyone: they sign in with Google or reset it ("Forgot password").
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->assignRole(Role::findOrCreate('user', 'web'));
            $created = true;
        } elseif (OrganisationRoles::roleIn($user, $organisation)) {
            return response()->json([
                'success' => false,
                'message' => 'This person is already in the organisation: change their role instead.',
                'errors' => ['email' => ['Already in this organisation.']],
            ], 422);
        }

        $row = OrganisationRoles::join($user, $organisation, $data['role'], $request->user()->id);
        static::logInfo('Person added to organisation', ['organisation' => $organisation->uid, 'user' => $user->uid, 'role' => $row->role, 'new_account' => $created, 'by' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => ($created ? 'Account made and added' : 'Added').' as '.strtolower(OrganisationUserRole::label($row->role)).'.',
            'data' => $this->row($row->fresh(['organisation', 'user']), $request),
        ], 201);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $row = $this->findRow($request, $uid);
        $data = $request->validate(['role' => ['required', Rule::in(OrganisationUserRole::ROLES)]]);
        $this->guardSelf($request, $row, 'change your own role');

        OrganisationRoles::setRole($row->user, $row->organisation, $data['role'], $request->user()->id);
        static::logInfo('Organisation role changed', ['organisation' => $row->organisation->uid, 'user' => $row->user->uid, 'role' => $data['role'], 'by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Role saved.', 'data' => $this->row($row->fresh(['organisation', 'user']), $request)]);
    }

    public function destroy(Request $request, string $uid): JsonResponse
    {
        $row = $this->findRow($request, $uid);
        $this->guardSelf($request, $row, 'remove yourself');

        OrganisationRoles::leave($row->user, $row->organisation);
        static::logInfo('Person removed from organisation', ['organisation' => $row->organisation->uid, 'user' => $row->user->uid, 'by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Removed from the organisation.']);
    }

    private function manageable(Request $request, string $organisationUid): Organisation
    {
        $organisation = Organisation::query()->where('uid', $organisationUid)->firstOrFail();
        abort_unless(OrganisationAccess::canAdminister($request->user(), $organisation), 403);

        return $organisation;
    }

    private function findRow(Request $request, string $uid): OrganisationUserRole
    {
        $row = OrganisationUserRole::query()->with(['organisation', 'user'])->where('uid', $uid)->first();
        abort_unless($row && $row->user && in_array((int) $row->organisation_id, OrganisationAccess::adminOrganisationIds($request->user()), true), 404);

        return $row;
    }

    /** An org-admin can't lock themselves out; a super-admin (or another org-admin) can change them. */
    private function guardSelf(Request $request, OrganisationUserRole $row, string $what): void
    {
        if ((int) $row->user_id === (int) $request->user()->id && ! OrganisationAccess::isPlatformAdmin($request->user())) {
            abort(response()->json(['success' => false, 'message' => "You can't {$what}: ask another organisation admin."], 422));
        }
    }

    private function row(OrganisationUserRole $row, ?Request $request = null): array
    {
        $isSelf = $request && (int) $row->user_id === (int) $request->user()?->id;
        $mayManage = ! $isSelf || OrganisationAccess::isPlatformAdmin($request?->user());

        return [
            // Record sections Role / Remove show only when this user may change them.
            'can_manage' => $mayManage ? '1' : '0',
            'is_self' => $isSelf,
            'uid' => $row->uid,
            'name' => $row->user?->name,
            'email' => $row->user?->email,
            'organisation_id' => $row->organisation_id,
            'organisation' => $row->organisation?->name,
            'role' => $row->role,
            'role_label' => OrganisationUserRole::label($row->role),
            'since' => $row->created_at?->format('j M Y'),
        ];
    }
}
