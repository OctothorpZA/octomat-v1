# API Routes & Controllers

## Route Structure

### Web Routes (`routes/web.php`)

```php
<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Public routes
Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
```

### Admin Routes (`routes/web.php`)

```php
// Admin routes with RBAC protection
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign')
        ->middleware('can:assign-roles'); // Custom gate for role assignment access
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post')
        ->middleware('can:assign-roles');
});

// Impersonation routes (protected by role middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:Super Admin')->group(function () {
        Route::post('/impersonate/take/{id}/{guardName?}', [Lab404\Impersonate\Controllers\ImpersonateController::class, 'take'])
            ->name('impersonate');
    });

    Route::post('/impersonate/leave', [Lab404\Impersonate\Controllers\ImpersonateController::class, 'leave'])
        ->name('impersonate.leave');
});
```

### Settings Routes (`routes/settings.php`)

```php
<?php
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Authenticated + verified routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
```

## Admin Routes (Sprint 4)

```php
Route::middleware(['auth', 'role:Super Admin'])->prefix('admin')->group(function () {
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign')
        ->middleware('can:assign-roles');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post');
});
```

## Controllers

### DashboardController

**Location**: `app/Http/Controllers/DashboardController.php`

#### Methods

- `index(Request $request): Response` - Unified dashboard with role-aggregated widgets

#### Key Logic

```php
public function index(Request $request): Response
{
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();

    // Aggregate widgets from ALL user roles (unified dashboard)
    $widgets = [];
    foreach ($userRoles as $role) {
        $widgets = array_merge($widgets, $this->getWidgetsForRole($role, $user));
    }

    // Sort by priority and limit to reasonable number
    $widgets = collect($widgets)
        ->sortBy('priority')
        ->take(9) // Max 9 widgets (3 rows of 3)
        ->values()
        ->all();

    return Inertia::render('dashboard', [
        'user' => $user,
        'userRoles' => $userRoles,
        'widgets' => $widgets,
    ]);
}

private function getWidgetsForRole(string $role, $user): array
{
    return match ($role) {
        'Super Admin' => $this->getSuperAdminWidgets($user),
        'Coach' => $this->getCoachWidgets($user),
        'Athlete' => $this->getAthleteWidgets($user),
        'Parent/Guardian' => $this->getParentWidgets($user),
        'Academy Owner' => $this->getAcademyOwnerWidgets($user),
        'Club Manager' => $this->getClubManagerWidgets($user),
        'General User' => $this->getGeneralUserWidgets($user),
        default => []
    };
}
```

### RoleAssignmentController

**Location**: `app/Http/Controllers/Admin/RoleAssignmentController.php`

#### Methods

- `index(Request $request): Response` - Admin role assignment interface with search and pagination
- `assign(Request $request): RedirectResponse` - Assign role to user with hierarchical validation

#### Key Logic

```php
public function assign(Request $request)
{
    $validated = $request->validate([
        'selectedUser' => 'required|exists:users,id',
        'selectedRole' => 'required|string|exists:roles,name',
    ]);

    // Advanced validation rules
    $user = User::find($validated['selectedUser']);
    $role = Role::where('name', $validated['selectedRole'])->first();

    // Prevent self-assignment of high-level roles (Super Admin and above)
    if ($request->user()->id === $user->id && $role->level >= 900) {
        return back()->withErrors(['authorization' => 'Cannot assign high-level administrative roles to yourself']);
    }

    // Check role level hierarchy (assignee cannot assign higher-level roles)
    if ($request->user()->getHighestRoleLevel() <= $role->level && ! $request->user()->hasRole('Super Admin')) {
        return back()->withErrors(['authorization' => 'Cannot assign roles at or above your authority level']);
    }

    // Execute role assignment
    $user->syncRoles([$validated['selectedRole']]);

    return redirect()->back()->with('success', 'Role assigned successfully!');
}
```

### ProfileController

**Location**: `app/Http/Controllers/Settings/ProfileController.php`

#### Methods

- `edit(Request $request): Response` - Show profile settings page
- `update(ProfileUpdateRequest $request): RedirectResponse` - Update user profile
- `destroy(ProfileDeleteRequest $request): RedirectResponse` - Delete user account

#### Key Logic

```php
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $request->user()->fill($request->validated());

    if ($request->user()->isDirty('email')) {
        $request->user()->email_verified_at = null;
    }

    $request->user()->save();

    return to_route('profile.edit');
}
```

### PasswordController

**Location**: `app/Http/Controllers/Settings/PasswordController.php`

#### Methods

- `edit(): Response` - Show password change form
- `update(PasswordUpdateRequest $request): RedirectResponse` - Update password

### TwoFactorAuthenticationController

**Location**: `app/Http/Controllers/Settings/TwoFactorAuthenticationController.php`

#### Methods

- `show(): Response` - Show 2FA settings page

### RoleAssignmentController

**Location**: `app/Http/Controllers/Admin/RoleAssignmentController.php`

#### Methods

- `index(): Response` - Show role assignment interface with users and available roles
- `assign(RoleAssignmentRequest $request): RedirectResponse` - Assign role to user with validation

#### Key Logic

```php
public function assign(RoleAssignmentRequest $request): RedirectResponse
{
    $user = User::findOrFail($request->user_id);
    $role = Role::findOrFail($request->role_id);

    // Remove existing roles and assign new one
    $user->syncRoles([$role]);

    return to_route('admin.roles.assign')
        ->with('success', "Role '{$role->name}' assigned to {$user->full_name} successfully.");
}
```

## Form Requests

### ProfileUpdateRequest

**Location**: `app/Http/Requests/Settings/ProfileUpdateRequest.php`

Validates profile update data:

- `name`: required, string, max 255
- `email`: required, string, email, max 255, unique

### PasswordUpdateRequest

**Location**: `app/Http/Requests/Settings/PasswordUpdateRequest.php`

Validates password update:

- `current_password`: required, current_password
- `password`: required, string, min 8, confirmed

### ProfileDeleteRequest

**Location**: `app/Http/Requests/Settings/ProfileDeleteRequest.php`

Validates account deletion:

- `password`: required, current_password

### RoleAssignmentRequest

**Location**: `app/Http/Requests/Admin/RoleAssignmentRequest.php`

Validates role assignment operations:

- `user_id`: required, exists in users table, different from current user
- `role_id`: required, exists in roles table, valid role permissions

#### Custom Validation Rules

```php
protected function prepareForValidation(): void
{
    // Prevent users from modifying their own roles
    if ($this->user_id == $this->user()->id) {
        $this->validator->errors()->add('user_id', 'You cannot modify your own role assignment.');
    }
}
```

## Middleware Usage

- `auth`: Requires authentication
- `verified`: Requires email verification
- `throttle:6,1`: Rate limiting for password updates (6 attempts per minute)

## Inertia.js Integration

All routes return Inertia responses that render React components:

- `welcome` - Landing page
- `dashboard` - User dashboard
- `settings/profile` - Profile settings
- `settings/appearance` - Appearance settings

## Route Naming Conventions

- `profile.edit` - Edit profile page
- `profile.update` - Update profile action
- `profile.destroy` - Delete account action
- `user-password.edit` - Password change page
- `user-password.update` - Password update action
- `appearance.edit` - Appearance settings page
- `two-factor.show` - 2FA settings page
- `admin.roles.assign` - Role assignment page
- `admin.roles.assign.post` - Role assignment action

## Security Features

- CSRF protection on all forms
- Rate limiting on sensitive operations
- Email verification requirements
- Password confirmation for destructive actions
- Role-based access control (RBAC) with hierarchical permissions
- Privilege escalation prevention (users cannot modify their own roles)
- Admin route protection with `can:assign-roles` middleware gate
- Hierarchical role system (100-1000 level) preventing unauthorized access</content>
  <parameter name="filePath">docs/RAG/api-routes.md
