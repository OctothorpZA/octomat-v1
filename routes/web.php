<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

use App\Http\Controllers\Admin\RoleAssignmentController;

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign')
        ->middleware('can:assign-roles'); // Custom gate for role assignment access
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post')
        ->middleware('can:assign-roles');
    Route::post('/roles/remove', [RoleAssignmentController::class, 'remove'])
        ->name('admin.roles.remove')
        ->middleware('can:assign-roles');
    Route::get('/audit', [RoleAssignmentController::class, 'audit'])
        ->name('admin.audit')
        ->middleware('role:Super Admin');
});

// Admin routes (protected)
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    // Role assignment functionality (dashboard is now unified)
});

// Laravel Impersonate routes (protected)
Route::middleware(['auth', 'verified'])->group(function () {
    // Only Super Admin can START impersonation
    Route::middleware(['role:Super Admin'])->group(function () {
        Route::post('/impersonate/take/{id}/{guardName?}', [Lab404\Impersonate\Controllers\ImpersonateController::class, 'take'])
            ->name('impersonate');
    });

    // ANY authenticated user can STOP impersonation (including impersonated users)
    Route::post('/impersonate/leave', [Lab404\Impersonate\Controllers\ImpersonateController::class, 'leave'])
        ->name('impersonate.leave');
});

require __DIR__.'/settings.php';
