<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Iquesters\Foundation\System\Http\ApiResponse;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Models\OrganisationUserRole;
use Iquesters\Organisation\Support\OrganisationAccess;
use Iquesters\Organisation\Support\OrganisationForms;
use Iquesters\Organisation\Support\OrganisationRoles;

/**
 * Organisations: the generic table (organisation-table, rows from data()) and
 * the organisation-details form schema, which posts JSON here. Everyone sees
 * the organisations they belong to; super-admins see and create all of them.
 */
class OrganisationController extends Controller
{
    use Loggable;

    public const TABLE = 'organisation-table';

    /** All Organisations: super-admins only; others open their own organisation (My organisation). */
    public function index(Request $request): RedirectResponse
    {
        if (! OrganisationAccess::isPlatformAdmin($request->user())) {
            return redirect()->route('organisations.mine');
        }

        return redirect()->to(self::tableUrl(self::TABLE));
    }

    /** Old "New organisation" page: the table's create form. */
    public function create(): RedirectResponse
    {
        $form = DB::table('form_schemas')->where('slug', OrganisationForms::DETAILS)->value('uid');

        return redirect()->to(self::tableUrl(self::TABLE).($form ? "/create/{$form}" : ''));
    }

    /** Old overview page: the organisation's record in the table. */
    public function show(Request $request, string $organisationUid): RedirectResponse
    {
        $organisation = Organisation::query()->where('uid', $organisationUid)->first();
        if (! $organisation || ! OrganisationAccess::canView($request->user(), $organisation)) {
            static::logWarning('Organisation not visible to user', ['organisation_uid' => $organisationUid, 'user_id' => $request->user()?->id]);

            return redirect()->route('organisations.index')->with('error', 'Organisation not found');
        }

        // The list is the super-admins'; everyone else sees the organisation's profile.
        if (! OrganisationAccess::isPlatformAdmin($request->user())) {
            return redirect()->route('organisations.profile.show', $organisation->uid);
        }

        return redirect()->to(self::recordUrl(self::TABLE, OrganisationForms::DETAILS, $organisation->uid));
    }

    public function edit(Request $request, string $organisationUid): RedirectResponse
    {
        return $this->show($request, $organisationUid);
    }

    /** The table's rows (data_url): super-admins only. */
    public function data(Request $request): JsonResponse
    {
        abort_unless(OrganisationAccess::isPlatformAdmin($request->user()), 403);

        $query = $this->visible($request)->withCount('userRoles as people_count');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like));
        }

        $offset = max(0, (int) $request->query('offset', 0));
        $length = min(100, max(1, (int) $request->query('length', 25)));
        $total = (clone $query)->count();
        $sort = (string) $request->query('sort', '');
        $column = ltrim($sort, '-');
        in_array($column, ['name', 'status', 'created_at'], true)
            ? $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc')
            : $query->orderBy('name');

        $rows = $query->skip($offset)->take($length)->get()->map(fn (Organisation $o) => $this->row($o, $request))->all();

        return ApiResponse::success($rows, 'OK', 200, ['pagination' => ['total' => $total, 'offset' => $offset, 'length' => $length]]);
    }

    public function record(Request $request, string $organisationUid): JsonResponse
    {
        abort_unless(OrganisationAccess::isPlatformAdmin($request->user()), 403);

        return ApiResponse::success($this->row($this->find($request, $organisationUid), $request));
    }

    /** Record section: the organisation's other pages. */
    public function links(Request $request, string $organisationUid): View
    {
        $organisation = $this->find($request, $organisationUid);

        return view('organisation::organisations.links', [
            'organisation' => $organisation,
            'canAdminister' => OrganisationAccess::canAdminister($request->user(), $organisation),
            'peopleUrl' => self::tableUrl(OrganisationPeopleController::TABLE).'?organisation='.$organisation->uid,
            'teamsUrl' => self::tableUrl(OrganisationTeamController::TABLE).'?organisation='.$organisation->uid,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organisation = Organisation::create($this->validated($request) + [
            'uid' => (string) Str::ulid(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        static::logInfo('Organisation created', ['organisation_uid' => $organisation->uid, 'user_id' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Organisation created.', 'data' => $this->row($organisation, $request)], 201);
    }

    /** Super-admins, and the organisation's org-admins (name and description; the status stays super-admin only). */
    public function update(Request $request, string $organisationUid): JsonResponse
    {
        $organisation = Organisation::query()->where('uid', $organisationUid)->firstOrFail();
        abort_unless(OrganisationAccess::canAdminister($request->user(), $organisation), 403);

        $data = $this->validated($request);
        if (! OrganisationAccess::isPlatformAdmin($request->user())) {
            $data['status'] = $organisation->status;
        }
        $organisation->update($data + ['updated_by' => $request->user()->id]);
        static::logInfo('Organisation updated', ['organisation_uid' => $organisation->uid, 'user_id' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Organisation saved.', 'data' => $this->row($organisation, $request)]);
    }

    /** Organisations aren't deleted (members, events and payments point at them): they are made inactive. */
    public function destroy(Request $request, string $organisationUid): JsonResponse
    {
        $organisation = Organisation::query()->where('uid', $organisationUid)->firstOrFail();
        $organisation->update(['status' => 'inactive', 'updated_by' => $request->user()->id]);
        static::logInfo('Organisation made inactive', ['organisation_uid' => $organisation->uid, 'user_id' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Organisation made inactive.']);
    }

    public static function tableUrl(string $slug): string
    {
        $uid = DB::table('table_schemas')->where('slug', $slug)->value('uid');
        abort_unless($uid, 404, 'Run organisation:seed to create the organisation tables.');

        return url("/list/{$uid}");
    }

    public static function recordUrl(string $table, string $form, string $recordUid): string
    {
        $formUid = DB::table('form_schemas')->where('slug', $form)->value('uid');

        return self::tableUrl($table).($formUid ? "/view/form/{$formUid}/data/{$recordUid}" : '');
    }

    /** @return Builder<Organisation> organisations the user may see (all but deleted ones for super-admins) */
    private function visible(Request $request): Builder
    {
        $user = $request->user();
        $query = Organisation::query()->where('status', '<>', 'deleted');

        if (OrganisationAccess::isPlatformAdmin($user)) {
            return $query;
        }

        $ids = array_unique(array_merge(OrganisationAccess::memberOrganisationIds($user), OrganisationAccess::adminOrganisationIds($user)));

        return $query->whereIn('id', $ids);
    }

    private function find(Request $request, string $organisationUid): Organisation
    {
        return $this->visible($request)->where('uid', $organisationUid)->firstOrFail();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        return ['name' => $data['name'], 'description' => $data['description'] ?? '', 'status' => $data['status'] ?? 'active'];
    }

    private function row(Organisation $organisation, Request $request): array
    {
        $role = OrganisationAccess::isPlatformAdmin($request->user())
            ? 'Super-admin'
            : (($r = OrganisationRoles::roleIn($request->user(), $organisation)) ? OrganisationUserRole::label($r) : '');

        return [
            'uid' => $organisation->uid,
            'name' => $organisation->name,
            'description' => $organisation->description,
            'status' => $organisation->status,
            'status_label' => ucfirst((string) $organisation->status),
            'people' => $organisation->people_count ?? null,
            'my_role' => $role,
            // The form's Edit / Delete buttons (user-interface reads "_can").
            '_can' => ['edit' => OrganisationAccess::canAdminister($request->user(), $organisation), 'delete' => false],
        ];
    }
}
