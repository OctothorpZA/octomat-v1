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

Route::middleware(['auth', 'role:Super Admin'])->prefix('admin')->group(function () {
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post');
});

require __DIR__.'/settings.php';
