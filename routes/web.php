<?php

use Illuminate\Support\Facades\Route;
use Iquesters\Organisation\Http\Controllers\OrganisationContextController;
use Iquesters\Organisation\Http\Controllers\OrganisationController;
use Iquesters\Organisation\Http\Controllers\OrganisationProfileController;
use Iquesters\Organisation\Http\Controllers\OrganisationTeamController;
use Iquesters\Organisation\Http\Controllers\OrganisationUserController;

Route::middleware('web')->group(function () {
    // Public page of an organisation (only when its admins switched it on).
    Route::get('o/{slug}', [OrganisationProfileController::class, 'publicShow'])
        ->where('slug', '[a-z0-9-]+')
        ->name('organisations.public');
    Route::get('organisations/{organisationUid}/logo', [OrganisationProfileController::class, 'logo'])
        ->name('organisations.logo');

    Route::middleware(['auth'])->group(function () {
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

        // Members: index lists only the user's own organisations; show checks membership.
        Route::prefix('organisations')->name('organisations.')->group(function () {
            Route::get('/', [OrganisationController::class, 'index'])->name('index');
            Route::get('{organisationUid}/show', [OrganisationController::class, 'show'])->name('show');
        });

        // Admin only: create, edit, delete, and user/team management.
        Route::prefix('organisations')->name('organisations.')->middleware('ensure.admin')->group(function () {
            Route::get('/create', [OrganisationController::class, 'create'])->name('create');
            Route::post('/', [OrganisationController::class, 'store'])->name('store');
            Route::get('{organisationUid}/edit', [OrganisationController::class, 'edit'])->name('edit');
            Route::put('{organisationUid}', [OrganisationController::class, 'update'])->name('update');
            Route::delete('{organisationUid}', [OrganisationController::class, 'destroy'])->name('destroy');

            Route::prefix('{organisationUid}/users')->name('users.')->group(function () {
                Route::get('/', [OrganisationUserController::class, 'usersIndex'])->name('index');
                Route::post('/', [OrganisationUserController::class, 'addUser'])->name('addUser');
                Route::get('/create', [OrganisationUserController::class, 'create'])->name('create');
                Route::delete('/{userUid}', [OrganisationUserController::class, 'removeUser'])->name('removeUser');
            });

            Route::prefix('{organisationUid}/teams')->name('teams.')->group(function () {
                Route::get('/', [OrganisationTeamController::class, 'teamsIndex'])->name('index');
                Route::get('/create', [OrganisationTeamController::class, 'create'])->name('create');
                Route::post('/', [OrganisationTeamController::class, 'store'])->name('store');
                Route::get('{teamUid}/edit', [OrganisationTeamController::class, 'edit'])->name('edit');
                Route::put('{teamUid}', [OrganisationTeamController::class, 'update'])->name('update');
                Route::delete('/{teamUid}', [OrganisationTeamController::class, 'destroy'])->name('destroy');

                Route::get('{teamUid}/show', [OrganisationTeamController::class, 'show'])->name('show');
                Route::get('{teamUid}/users', [OrganisationTeamController::class, 'teamUsersIndex'])->name('users.index');
                Route::post('{teamUid}/users', [OrganisationTeamController::class, 'addUser'])->name('users.addUser');
                Route::delete('{teamUid}/users/{userUid}', [OrganisationTeamController::class, 'removeUser'])->name('users.removeUser');
            });
        });
    });
});
