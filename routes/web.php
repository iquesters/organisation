<?php

use Illuminate\Support\Facades\Route;
use Iquesters\Organisation\Http\Controllers\OrganisationContextController;
use Iquesters\Organisation\Http\Controllers\OrganisationController;
use Iquesters\Organisation\Http\Controllers\OrganisationPeopleController;
use Iquesters\Organisation\Http\Controllers\OrganisationProfileController;
use Iquesters\Organisation\Http\Controllers\OrganisationTeamController;

Route::middleware('web')->group(function () {
    // Public page of an organisation (only when its admins switched it on): /o/{uid}.
    // An old address by name (/o/{slug}) redirects to it.
    Route::get('o/{key}', [OrganisationProfileController::class, 'publicShow'])
        ->where('key', '[A-Za-z0-9-]+')
        ->name('organisations.public');
    Route::get('organisations/{organisationUid}/logo', [OrganisationProfileController::class, 'logo'])
        ->name('organisations.logo');
    // The organisation's own Terms / Privacy policy (public: read before applying).
    Route::get('organisations/{organisationUid}/{kind}', [OrganisationProfileController::class, 'policy'])
        ->where('kind', 'terms|privacy')
        ->name('organisations.policy');

    Route::middleware(['auth'])->group(function () {
        $ulid = '[0-9A-Za-z]{26}';

        // The organisation the user works in (header switcher), and "My organisation".
        Route::post('organisations/context', [OrganisationContextController::class, 'update'])->name('organisations.context.update');
        Route::delete('organisations/context', [OrganisationContextController::class, 'destroy'])->name('organisations.context.destroy');
        Route::get('organisations/mine', [OrganisationContextController::class, 'mine'])->name('organisations.mine');

        // Profile: members view it; its admins (and super-admin) edit it. Checked in the controller.
        Route::prefix('organisations/{organisationUid}/profile')->name('organisations.profile.')->group(function () {
            Route::get('/', [OrganisationProfileController::class, 'show'])->name('show');
            Route::get('/edit', [OrganisationProfileController::class, 'edit'])->name('edit');
            Route::put('/', [OrganisationProfileController::class, 'update'])->name('update');
        });

        // Organisations (generic table): everyone sees theirs; checked in the controller.
        Route::prefix('organisations')->name('organisations.')->group(function () use ($ulid) {
            Route::get('/', [OrganisationController::class, 'index'])->name('index');
            Route::get('data', [OrganisationController::class, 'data'])->name('data');
            Route::get('data/{organisationUid}', [OrganisationController::class, 'record'])->where('organisationUid', $ulid)->name('record');
            Route::get('{organisationUid}/links', [OrganisationController::class, 'links'])->where('organisationUid', $ulid)->name('links');
            Route::get('{organisationUid}/show', [OrganisationController::class, 'show'])->name('show');
            // Super-admins and the organisation's org-admins (checked in the controller).
            Route::put('{organisationUid}', [OrganisationController::class, 'update'])->where('organisationUid', $ulid)->name('update');

            // People and teams: super-admins and the organisation's org-admins (checked in the controller).
            Route::get('{organisationUid}/users', [OrganisationPeopleController::class, 'index'])->name('users.index');
            Route::get('{organisationUid}/users/create', [OrganisationPeopleController::class, 'create'])->name('users.create');
            Route::get('{organisationUid}/teams', [OrganisationTeamController::class, 'index'])->name('teams.index');
        });

        // Creating and changing organisations: super-admins only.
        Route::prefix('organisations')->name('organisations.')->middleware('ensure.admin')->group(function () use ($ulid) {
            Route::get('/create', [OrganisationController::class, 'create'])->name('create');
            Route::post('/', [OrganisationController::class, 'store'])->name('store');
            Route::get('{organisationUid}/edit', [OrganisationController::class, 'edit'])->name('edit');
            Route::delete('{organisationUid}', [OrganisationController::class, 'destroy'])->where('organisationUid', $ulid)->name('destroy');
        });

        // People of an organisation and their role there (JSON for the form engine).
        Route::prefix('organisation-people')->name('organisations.people.')->group(function () use ($ulid) {
            Route::get('/', [OrganisationPeopleController::class, 'all'])->name('index');
            Route::get('data', [OrganisationPeopleController::class, 'data'])->name('data');
            Route::get('data/{uid}', [OrganisationPeopleController::class, 'record'])->where('uid', $ulid)->name('record');
            Route::get('{uid}/summary', [OrganisationPeopleController::class, 'summary'])->where('uid', $ulid)->name('summary');
            Route::get('options/organisations', [OrganisationPeopleController::class, 'organisationOptions'])->name('options.organisations');
            Route::get('options/users', [OrganisationPeopleController::class, 'userOptions'])->name('options.users');
            Route::post('/', [OrganisationPeopleController::class, 'store'])->name('store');
            Route::put('{uid}', [OrganisationPeopleController::class, 'update'])->where('uid', $ulid)->name('update');
            Route::delete('{uid}', [OrganisationPeopleController::class, 'destroy'])->where('uid', $ulid)->name('destroy');
        });

        Route::prefix('organisation-teams')->name('organisations.team.')->group(function () use ($ulid) {
            Route::get('/', [OrganisationTeamController::class, 'all'])->name('index');
            Route::get('data', [OrganisationTeamController::class, 'data'])->name('data');
            Route::get('data/{uid}', [OrganisationTeamController::class, 'record'])->where('uid', $ulid)->name('record');
            Route::get('options/people', [OrganisationTeamController::class, 'peopleOptions'])->name('options.people');
            Route::post('/', [OrganisationTeamController::class, 'store'])->name('store');
            Route::put('{uid}', [OrganisationTeamController::class, 'update'])->where('uid', $ulid)->name('update');
            Route::delete('{uid}', [OrganisationTeamController::class, 'destroy'])->where('uid', $ulid)->name('destroy');
        });
    });
});
