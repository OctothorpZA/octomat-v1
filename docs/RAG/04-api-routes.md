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
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
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

## Controllers

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

## Security Features

- CSRF protection on all forms
- Rate limiting on sensitive operations
- Email verification requirements
- Password confirmation for destructive actions</content>
  <parameter name="filePath">docs/RAG/api-routes.md
