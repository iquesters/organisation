<?php

use Illuminate\Support\Facades\Route;
use Iquesters\Organisation\Http\Controllers\OrganisationController;

Route::middleware('web')->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::prefix('organisations')->name('organisations.')->group(function () {
            Route::get('/', [OrganisationController::class, 'index'])->name('index');
            Route::get('/create', [OrganisationController::class, 'create'])->name('create');
            Route::post('/', [OrganisationController::class, 'store'])->name('store');
            Route::get('{organisationUid}/show', [OrganisationController::class, 'show'])->name('show');
            Route::get('{organisationUid}/edit', [OrganisationController::class, 'edit'])->name('edit');
            Route::put('{organisationUid}', [OrganisationController::class, 'update'])->name('update');
            Route::delete('{organisationUid}', [OrganisationController::class, 'destroy'])->name('destroy');
        });
    });
});