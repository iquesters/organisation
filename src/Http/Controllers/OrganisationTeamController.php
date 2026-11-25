<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Iquesters\Foundation\Support\UserModel;
use Iquesters\Foundation\System\Http\ApiResponse;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Models\Team;
use Iquesters\Organisation\Support\OrganisationAccess;

/**
 * Teams of an organisation: the generic table (organisation-team-table,
 * ?organisation={uid}) and the organisation-team-details form schema, which
 * also chooses the team's people (people of the organisation).
 * Super-admins and the organisation's org-admins manage them.
 */
class OrganisationTeamController extends Controller
{
    use Loggable;

    public const TABLE = 'organisation-team-table';

    /** Sidebar "Teams": every organisation the user manages (the organisation they work in, if chosen). */
    public function all(Request $request): RedirectResponse
    {
        abort_unless(OrganisationAccess::adminOrganisationIds($request->user()) !== [] || OrganisationAccess::isPlatformAdmin($request->user()), 403);
        $context = app(\Iquesters\Organisation\Support\OrganisationContext::class)->current();
        $filter = $context && OrganisationAccess::canAdminister($request->user(), $context) ? '?organisation='.$context->uid : '';

        return redirect()->to(OrganisationController::tableUrl(self::TABLE).$filter);
    }

    /** Old Teams tab (and its pages): the table filtered to the organisation. */
    public function index(Request $request, string $organisationUid): RedirectResponse
    {
        $organisation = Organisation::query()->where('uid', $organisationUid)->firstOrFail();
        abort_unless(OrganisationAccess::canAdminister($request->user(), $organisation), 403);

        return redirect()->to(OrganisationController::tableUrl(self::TABLE).'?organisation='.$organisation->uid);
    }

    public function data(Request $request): JsonResponse
    {
        $ids = OrganisationAccess::adminOrganisationIds($request->user());
        if ($uid = $request->query('organisation')) {
            $id = Organisation::query()->where('uid', $uid)->value('id');
            $ids = in_array((int) $id, $ids, true) ? [(int) $id] : [];
        }

        $query = Team::query()->whereIn('id', $this->teamIdsOf($ids))->with('organisations:id,name')->withCount('users');
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        $offset = max(0, (int) $request->query('offset', 0));
        $length = min(100, max(1, (int) $request->query('length', 25)));
        $total = (clone $query)->count();
        $rows = $query->orderBy('name')->skip($offset)->take($length)->get()->map(fn (Team $t) => $this->row($t))->all();

        return ApiResponse::success($rows, 'OK', 200, ['pagination' => ['total' => $total, 'offset' => $offset, 'length' => $length]]);
    }

    public function record(Request $request, string $uid): JsonResponse
    {
        return ApiResponse::success($this->row($this->find($request, $uid)));
    }

    /** People of the team's organisation (checkbox options); empty until the team is saved. */
    public function peopleOptions(Request $request): JsonResponse
    {
        $team = $request->query('team') ? $this->find($request, (string) $request->query('team'), false) : null;
        $organisation = $team?->organisations->first();
        if (! $organisation) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $people = UserModel::query()
            ->whereHas('organisations', fn ($q) => $q->where('organisations.id', $organisation->id))
            ->orderBy('name')->get(['id', 'uid', 'name', 'email']);

        return response()->json(['success' => true, 'data' => $people->map(fn ($u) => ['value' => $u->uid, 'label' => $u->name.' ('.$u->email.')'])->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $organisation = Organisation::query()->findOrFail($data['organisation_id']);

        $team = DB::transaction(function () use ($data, $organisation, $request) {
            $team = Team::create([
                'uid' => (string) Str::ulid(),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'active',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $organisation->assignTeam($team);

            return $team;
        });
        static::logInfo('Team created', ['team' => $team->uid, 'organisation' => $organisation->uid, 'by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Team created. Open it to choose its people.', 'data' => $this->row($team->fresh())], 201);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $team = $this->find($request, $uid);
        $data = $this->validated($request);
        $organisation = $team->organisations->first();

        DB::transaction(function () use ($team, $data, $organisation, $request) {
            $team->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'active',
                'updated_by' => $request->user()->id,
            ]);

            if ($organisation && array_key_exists('user_uids', $data)) {
                // Only people of the organisation can be in its team.
                $ids = UserModel::query()
                    ->whereIn('uid', (array) ($data['user_uids'] ?? []))
                    ->whereHas('organisations', fn ($q) => $q->where('organisations.id', $organisation->id))
                    ->pluck('id')->all();
                $team->users()->sync($ids);
            }
        });

        return response()->json(['success' => true, 'message' => 'Team saved.', 'data' => $this->row($team->fresh())]);
    }

    /** Teams are removed with their links (people stay in the organisation). */
    public function destroy(Request $request, string $uid): JsonResponse
    {
        $team = $this->find($request, $uid);
        DB::transaction(function () use ($team) {
            DB::table('model_has_teams')->where('team_id', $team->id)->delete();
            $team->delete();
        });

        return response()->json(['success' => true, 'message' => 'Team removed.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'organisation_id' => ['required', 'integer', Rule::in(OrganisationAccess::adminOrganisationIds($request->user()))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:active,inactive'],
            'user_uids' => ['sometimes', 'nullable', 'array'],
            'user_uids.*' => ['string', 'max:26'],
        ], ['organisation_id.in' => "You don't manage this organisation."]);
    }

    /** @return int[] teams linked to these organisations */
    private function teamIdsOf(array $organisationIds): array
    {
        return DB::table('model_has_teams')
            ->where('model_type', (new Organisation)->getMorphClass())
            ->whereIn('model_id', $organisationIds)
            ->pluck('team_id')->map(fn ($id) => (int) $id)->all();
    }

    private function find(Request $request, string $uid, bool $abort = true): ?Team
    {
        $team = Team::query()->with('organisations:id,uid,name')->where('uid', $uid)
            ->whereIn('id', $this->teamIdsOf(OrganisationAccess::adminOrganisationIds($request->user())))
            ->first();
        if (! $team && $abort) {
            abort(404);
        }

        return $team;
    }

    private function row(Team $team): array
    {
        $team->loadMissing('organisations:id,name');
        $organisation = $team->organisations->first();

        return [
            'uid' => $team->uid,
            'name' => $team->name,
            'description' => $team->description,
            'status' => $team->status,
            'status_label' => ucfirst((string) $team->status),
            'organisation_id' => $organisation?->id,
            'organisation' => $organisation?->name,
            'people' => $team->users_count ?? $team->users()->count(),
            'user_uids' => $team->users()->pluck('uid')->all(),
        ];
    }
}
