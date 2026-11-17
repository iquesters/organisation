<?php

namespace Iquesters\Organisation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Iquesters\Foundation\Http\Middleware\EnsureAdmin;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\Organisation\Models\Organisation;

class OrganisationController extends Controller
{
    use Loggable;

    public function index()
    {
        try {
            static::logInfo('Fetching organisations');

            $user = Auth::user();

            // ✅ Super Admin → see all organisations
            if ($user->hasRole('super-admin')) {
                static::logInfo('User is super-admin, fetching all organisations');

                $organisations = Organisation::where('status', '<>', 'deleted')->get();

                static::logDebug('Organisations fetched successfully', [
                    'count' => $organisations->count(),
                ]);

                return view('organisation::organisations.index', compact('organisations'));
            }

            // ✅ Non super-admin → only user's organisations
            $organisations = $user->organisations()
                ->where('status', '<>', 'deleted')
                ->get();

            $count = $organisations->count();

            static::logDebug('User organisations fetched', ['count' => $count]);

            // ✅ Only ONE organisation → redirect to show
            if ($count === 1) {
                return redirect()->route(
                    'organisations.show',
                    $organisations->first()->uid
                );
            }

            // ✅ More than one → show index
            return view('organisation::organisations.index', compact('organisations'));

        } catch (\Exception $e) {
            static::logError('Failed to fetch organisations', [
                'error' => $e->getMessage(),
            ]);

            static::logDebug($e->getTraceAsString());

            return redirect()
                ->route('dashboard')
                ->with('error', 'Failed to load organisations');
        }
    }

    // Show create form
    public function create()
    {
        return view('organisation::organisations.form');
    }

    // Store a new organisation
    public function store(Request $request)
    {
        try {
            static::logInfo('Attempting to create new organisation');

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                static::logWarning('Organisation validation failed', ['errors' => $validator->errors()]);

                return redirect()->back()->withErrors($validator)->withInput();
            }

            $organisation = Organisation::create([
                'uid' => Str::ulid(),
                'name' => $request->name,
                'description' => $request->description ?? '',
                'status' => $request->status ?? 'unknown',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            static::logInfo('Organisation created successfully', ['organisation_uid' => $organisation->uid]);

            return redirect()->route('organisations.show', $organisation->uid)
                ->with('success', 'Organisation created successfully');
        } catch (\Exception $e) {
            static::logError('Failed to create organisation', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to create organisation')->withInput();
        }
    }

    // Show a specific organisation
    public function show($organisationUid)
    {
        try {
            static::logInfo('Fetching organisation', ['organisation_uid' => $organisationUid]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();

            // Non-admins may only view organisations they belong to.
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole(EnsureAdmin::roles());
            if (! $isAdmin && ! $user->organisations()->whereKey($organisation->id)->exists()) {
                throw new \Exception('User is not a member of organisation uid: '.$organisationUid);
            }

            static::logDebug('Organisation fetched successfully', ['organisation' => $organisation->toArray()]);

            return view('organisation::organisations.show', compact('organisation'));
        } catch (\Exception $e) {
            static::logError('Failed to fetch organisation', [
                'organisation_uid' => $organisationUid,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('organisations.index')->with('error', 'Organisation not found');
        }
    }

    // Show edit form
    public function edit($organisationUid)
    {
        try {
            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            if (! isset($organisation)) {
                throw new \Exception('Organisation not found uid: '.$organisationUid);
            }

            return view('organisation::organisations.form', compact('organisation'));
        } catch (\Exception $e) {
            static::logError('Failed to load edit form', ['organisation_uid' => $organisationUid, 'error' => $e->getMessage()]);

            return redirect()->route('organisations.index')->with('error', $e->getMessage());
        }
    }

    // Update an organisation
    public function update(Request $request, $organisationUid)
    {
        try {
            static::logInfo('Attempting to update organisation', ['organisation_uid' => $organisationUid]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            if (! isset($organisation)) {
                throw new \Exception('Organisation not found uid: '.$organisationUid);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                static::logWarning('Organisation update validation failed', ['errors' => $validator->errors()]);

                return redirect()->back()->withErrors($validator)->withInput();
            }

            $organisation->update([
                'name' => $request->name ?? $organisation->name,
                'description' => $request->description ?? $organisation->description,
                'status' => $request->status ?? $organisation->status,
                'updated_by' => Auth::user()->id ?? $organisation->updated_by,
            ]);

            static::logInfo('Organisation updated successfully', ['organisation_uid' => $organisationUid]);

            return redirect()->route('organisations.show', $organisation->uid)
                ->with('success', 'Organisation updated successfully');
        } catch (\Exception $e) {
            static::logError('Failed to update organisation', ['organisation_uid' => $organisationUid, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to update organisation')->withInput();
        }
    }

    // Delete an organisation
    public function destroy($organisationUid)
    {
        try {
            static::logInfo('Attempting to delete organisation', ['organisation_uid' => $organisationUid]);

            $organisation = Organisation::where('uid', $organisationUid)->firstOrFail();
            if (! isset($organisation)) {
                throw new \Exception('Organisation not found uid: '.$organisationUid);
            }

            $organisation->delete();

            static::logInfo('Organisation deleted successfully', ['organisation_uid' => $organisationUid]);

            return redirect()->route('organisations.index')
                ->with('success', 'Organisation deleted successfully');
        } catch (\Exception $e) {
            static::logError('Failed to delete organisation', ['organisation_uid' => $organisationUid, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to delete organisation');
        }
    }
}
