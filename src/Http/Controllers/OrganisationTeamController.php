<?php


namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Iquesters\Organisation\Models\Organisation;
use Iquesters\Organisation\Models\Team;

class OrganisationTeamController extends Controller
{
    public function teamsIndex(string $organisationUid)
    {
        try {
            Log::info('Fetching organisation teams', [
                'organisationUid' => $organisationUid,
            ]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch teams belonging to this organisation
            $teams = Team::whereHas('organisations', function ($query) use ($organisation) {
                $query->where('organisations.id', $organisation->id);
                })->get();

            Log::debug('Organisation teams fetched', [
                'organisation_id' => $organisation->id,
                'count' => $teams->count(),
            ]);

            return view(
                'organisation::teams.index',
                compact('organisation', 'teams')
            );

        } catch (\Exception $e) {
            Log::error('Failed to fetch organisation teams', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function create(string $organisationUid)
    {
        try {
            Log::info('Opening create team page', [
                'organisationUid' => $organisationUid
            ]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            return view('organisation::teams.form', compact('organisation'));

        } catch (\Exception $e) {
            Log::error('Failed to open create team page', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Unable to open team creation page.');
        }
    }
    
    public function store(Request $request, string $organisationUid)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            $team = Team::create([
                'uid' => Str::ulid(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Attach team to organisation
            $organisation->assignTeam($team);

            Log::info('Team created', [
                'team_uid' => $team->uid,
                'organisation_uid' => $organisationUid
            ]);

            return redirect()->route('organisations.teams.index', $organisation->uid)->with('success', 'Team created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create team', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to create team.');
        }
    }

    public function edit(string $organisationUid, string $teamUid)
    {
        try {
            Log::info('Opening edit team page', [
                'organisation_uid' => $organisationUid,
                'team_uid' => $teamUid,
                'user_id' => auth()->id(),
            ]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            $team = Team::where('uid', $teamUid)->firstOrFail();

            // Safety check: ensure team belongs to organisation
            if (! $organisation->hasTeam($team)) {
                Log::warning('Team does not belong to organisation', [
                    'organisation_uid' => $organisationUid,
                    'team_uid' => $teamUid,
                ]);

                return redirect()->back()->with(
                    'error',
                    'Team does not belong to this organisation.'
                );
            }

            Log::debug('Edit team data loaded', [
                'team_id' => $team->id,
                'organisation_id' => $organisation->id,
            ]);

            return view(
                'organisation::teams.form',
                compact('organisation', 'team')
            );

        } catch (\Exception $e) {
            Log::error('Failed to open edit team page', [
                'organisation_uid' => $organisationUid,
                'team_uid' => $teamUid,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Unable to open edit team page.');
        }
    }
    
    public function update(Request $request, string $organisationUid, string $teamUid)
    {
        try {
            Log::info('Updating team', [
                'organisation_uid' => $organisationUid,
                'team_uid' => $teamUid,
                'user_id' => auth()->id(),
            ]);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            $team = Team::where('uid', $teamUid)->firstOrFail();

            // Safety check
            if (! $organisation->hasTeam($team)) {
                Log::warning('Attempt to update team not linked to organisation', [
                    'organisation_uid' => $organisationUid,
                    'team_uid' => $teamUid,
                ]);

                return redirect()->back()->with(
                    'error',
                    'Team does not belong to this organisation.'
                );
            }

            $team->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'updated_by' => auth()->id(),
            ]);

            Log::info('Team updated successfully', [
                'team_uid' => $team->uid,
                'organisation_uid' => $organisationUid,
            ]);

            return redirect()
                ->route('organisations.teams.show', ['organisationUid' => $organisationUid,
                'teamUid' => $teamUid,])
                ->with('success', 'Team updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update team', [
                'organisation_uid' => $organisationUid,
                'team_uid' => $teamUid,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update team.');
        }
    }

    public function destroy(string $organisationUid, string $teamUid)
    {
        try {
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            $team = Team::where('uid', $teamUid)->firstOrFail();

            // Optional: ensure team belongs to organisation
            if (! $organisation->teams()->where('teams.id', $team->id)->exists()) {
                return redirect()->back()->with('error', 'Team does not belong to this organisation.');
            }

            $team->update([
                'status' => 'deleted',
                'updated_by' => auth()->id(),
            ]);

            Log::info('Team deleted (soft)', [
                'team_uid' => $teamUid,
                'organisation_uid' => $organisationUid
            ]);

            return redirect()->back()->with('success', 'Team deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete team', [
                'organisationUid' => $organisationUid,
                'teamUid' => $teamUid,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to delete team.');
        }
    }
    
    public function show(string $organisationUid, string $teamUid)
    {
        try {
            Log::info('Fetching team', ['organisation_uid' => $organisationUid, 'team_uid' => $teamUid]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch the team within this organisation
            $team = $organisation->teams()->where('uid', $teamUid)->firstOrFail();

            Log::debug('Team fetched successfully', ['team' => $team->toArray()]);

            return view('organisation::teams.show', compact('organisation', 'team'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch team', [
                'organisationUid' => $organisationUid,
                'teamUid' => $teamUid,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Team not found.');
        }
    }

}