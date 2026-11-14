<?php


namespace Iquesters\Organisation\Http\Controllers;

use Iquesters\Foundation\System\Traits\Loggable;
use Illuminate\Routing\Controller;
use Iquesters\Foundation\Support\UserModel;
use Spatie\Permission\Models\Role;
use Iquesters\Organisation\Models\Organisation;
use Illuminate\Http\Request;

class OrganisationUserController extends Controller
{
    use Loggable;

    public function usersIndex(string $organisationUid)
    {
        try {
            static::logInfo('Fetching users for organisation', [
                'organisationUid' => $organisationUid
            ]);

            // Fetch organisation
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Users already in this organisation
            $users = UserModel::query()->whereHas('organisations', function ($query) use ($organisation) {
                    $query->where('organisations.id', $organisation->id);
                })
                ->with('organisations')
                ->get();

            // Users NOT in this organisation (available users)
            $availableUsers = UserModel::query()->whereDoesntHave('organisations', function ($query) use ($organisation) {
                    $query->where('organisations.id', $organisation->id);
                })
                ->get();

            static::logDebug('Organisation users fetched', [
                'organisation_id' => $organisation->id,
                'assigned' => $users->count(),
                'available' => $availableUsers->count()
            ]);

            return view(
                'organisation::users.index',
                compact('organisation', 'users', 'availableUsers')
            );

        } catch (\Exception $e) {
            static::logError('Failed to fetch organisation users', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to load organisation users');
        }
    }

    
    public function addUser(Request $request, string $organisationUid)
    {
        try {
            // ✅ Validate correctly
            $request->validate([
                'user_id' => ['required', 'string', 'exists:users,uid'],
            ]);

            static::logDebug('Adding user to organisation', [
                'organisationUid' => $organisationUid,
                'userUid' => $request->user_id,
            ]);

            // Fetch organisation
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch user by UID
            $user = UserModel::query()->where('uid', $request->user_id)->firstOrFail();

            // Prevent duplicate assignment
            if ($user->hasOrganisation($organisation)) {
                return redirect()
                    ->back()
                    ->with('error', 'User is already part of this organisation.');
            }

            // ✅ Correct trait method
            $user->assignOrganisation($organisation);

            static::logInfo('User assigned to organisation', [
                'organisation_uid' => $organisation->uid,
                'user_uid' => $user->uid,
            ]);

            return redirect()
                ->back()
                ->with('success', 'User added to organisation successfully.');

        } catch (\Exception $e) {
            static::logError('Failed to add user to organisation', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to add user to organisation.');
        }
    }
    
    public function create(string $organisationUid)
    {
        try {
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            $excludedRoles = [
                'super-admin',
            ];
            $roles = Role::whereNotIn('name', $excludedRoles)->get();

            return view('usermanagement::users.create', compact('organisation', 'roles'));

        } catch (\Exception $e) {
            static::logError('Failed to load organisation user create page', [
                'organisation_uid' => $organisationUid,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Unable to load page.');
        }
    }
    
    public function removeUser(string $organisationUid, string $userUid)
    {
        try {
            // Fetch organisation by UID
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch user by UID
            $user = UserModel::query()->where('uid', $userUid)->firstOrFail();

            // Check if the user is actually assigned to this organisation
            if (!$user->hasOrganisation($organisation)) {
                return redirect()
                    ->back()
                    ->with('error', 'User is not associated with this organisation.');
            }

            // Remove the user from the organisation
            $user->removeOrganisation($organisation);

            return redirect()
                ->back()
                ->with('success', 'User removed from organisation successfully.');

        } catch (\Exception $e) {
            static::logError('Failed to remove user from organisation', [
                'organisationUid' => $organisationUid,
                'userUid' => $userUid,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to remove user from organisation.');
        }
    }

}