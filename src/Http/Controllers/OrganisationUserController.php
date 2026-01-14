<?php


namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Iquesters\Organisation\Models\Organisation;

class OrganisationUserController extends Controller
{
    public function usersIndex(string $organisationUid)
    {
        try {
            Log::info('Fetching users for organisation', [
                'organisationUid' => $organisationUid
            ]);

            // Fetch organisation by UID
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch users belonging to this organisation
            $users = User::whereHas('organisations', function ($query) use ($organisation) {
                $query->where('organisations.id', $organisation->id);
            })
            ->with('organisations')
            ->get();

            Log::debug('Organisation users fetched', [
                'organisation_id' => $organisation->id,
                'count' => $users->count()
            ]);

            return view(
                'organisation::users.index',
                compact('organisation', 'users')
            );

        } catch (\Exception $e) {
            Log::error('Failed to fetch organisation users', [
                'organisationUid' => $organisationUid,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to load organisation users');
        }
    }
    
    public function addUser($organisationUid)
    {
        
    }
    
    public function removeUser(string $organisationUid, string $userUid)
    {
        try {
            // Fetch organisation by UID
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Fetch user by UID
            $user = User::where('uid', $userUid)->firstOrFail();

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
            Log::error('Failed to remove user from organisation', [
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