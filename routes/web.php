<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

use App\Http\Controllers\Admin\RoleAssignmentController;

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign')
        ->middleware('can:assign-roles'); // Custom gate for role assignment access
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post')
        ->middleware('can:assign-roles');
});

// Mirror impersonation routes (protected)
Route::middleware(['auth', 'verified'])->group(function () {
    // Only super_admin can access these
    Route::middleware(['role:super_admin', 'mirror.ttl'])->group(function () {
        Route::post('/impersonate/{user}', [App\Http\Controllers\ImpersonationController::class, 'start'])
            ->name('impersonate.start');
        Route::post('/impersonate/stop', [App\Http\Controllers\ImpersonationController::class, 'stop'])
            ->name('impersonate.stop');
    });
});

require __DIR__.'/settings.php';
