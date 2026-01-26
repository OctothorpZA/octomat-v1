# Testing Structure & Patterns

## Test Organization

### Test Types

- **Feature Tests**: `tests/Feature/` - Test complete user workflows
- **Unit Tests**: `tests/Unit/` - Test individual classes/methods
- **Browser Tests**: `tests/Browser/` (future) - E2E browser testing with Pest
- **JavaScript Tests**: `resources/js/hooks/__tests__/` - React hook testing with Vitest

### Test Files Structure

```
tests/
├── Feature/
│   ├── Admin/
│   │   └── RoleTest.php
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── PasswordConfirmationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── RegistrationTest.php
│   │   └── TwoFactorChallengeTest.php
│   ├── DashboardTest.php
│   ├── ExampleTest.php
│   ├── ImpersonationTest.php
│   ├── UnifiedDashboardTest.php
│   └── Settings/
│       ├── PasswordUpdateTest.php
│       ├── ProfileUpdateTest.php
│       └── TwoFactorAuthenticationTest.php
├── js/
│   ├── components/
│   │   ├── Dashboard.test.tsx
│   │   └── RoleAssignment.test.tsx
│   └── hooks/
│       └── use-debounce.test.ts
├── Unit/
│   └── ExampleTest.php
├── TestCase.php
├── Pest.php
└── Browser/ (future)
```

## Pest Testing Framework

### Basic Test Structure

```php
<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});
```

### Test Assertions

```php
// HTTP Response Assertions
$response->assertOk();           // 200 status
$response->assertSuccessful();   // 2xx status
$response->assertRedirect();     // 3xx status
$response->assertForbidden();    // 403 status
$response->assertNotFound();     // 404 status

// Content Assertions
$response->assertSee('Welcome');         // Text present
$response->assertDontSee('Error');       // Text absent
$response->assertSeeInOrder(['First', 'Second']); // Ordered text

// Database Assertions
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);

// Authentication Assertions
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);
$this->assertGuest();
```

## Testing Patterns

### Authentication Testing

```php
test('users can authenticate', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});
```

### Form Validation Testing

```php
test('profile update validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [])
        ->assertSessionHasErrors(['name', 'email']);
});
```

### Feature Testing with Datasets

```php
test('password validation rules', function (string $password, bool $shouldPass) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertSessionHasErrors($shouldPass ? [] : ['password']);
})->with([
    ['short', false],           // Too short
    ['validpassword123', true], // Valid
    ['nouppercase123', false],  // Missing uppercase
    ['NOLOWERCASE123', false],  // Missing lowercase
]);
```

### RoleTest (15 Tests)

**File**: `tests/Feature/Admin/RoleTest.php`

Tests role assignment, removal, authorization, validation, conflicts, hierarchy.

```php
test('super admin can assign roles', function () {
    // Super Admin → Coach Johnson 'Parent/Guardian' success
    $this->actingAs($superAdmin)
        ->post(route('admin.roles.assign.post'), [
            'selectedUser' => $coach->id,
            'selectedRole' => 'Parent/Guardian',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
```

### RoleTest (Sprint 4)

**File**: `tests/Feature/Admin/RoleTest.php` (15 tests)

Tests role assignment, removal, authorization, validation, conflicts, hierarchy.

- Super Admin assign success
- Hierarchy validation
- Conflict detection
- Duplicate prevention
- Self-assignment block

## Model Factories

### User Factory

```php
<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

### Factory States

```php
// Usage in tests
$user = User::factory()->create();
$unverifiedUser = User::factory()->unverified()->create();
```

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="testing"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

### RBAC Testing Patterns

```php
test('role assignment requires proper permissions', function () {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $role = Role::create(['name' => 'Manager']);

    // Test unauthorized access
    $this->actingAs($user)
        ->get(route('admin.roles.assign'))
        ->assertForbidden();

    // Test with proper permissions
    $user->assignRole('Super Admin');

    $this->actingAs($user)
        ->get(route('admin.roles.assign'))
        ->assertOk();
});

test('role assignment validates input', function () {
    $admin = User::factory()->create()->assignRole('Super Admin');

    $this->actingAs($admin)
        ->post(route('admin.roles.assign.post'), [
            'user_id' => 999, // Non-existent user
            'role_id' => 999, // Non-existent role
        ])
        ->assertSessionHasErrors(['user_id', 'role_id']);
});

test('users cannot assign roles to themselves', function () {
    $user = User::factory()->create()->assignRole('Super Admin');

    $this->actingAs($user)
        ->post(route('admin.roles.assign.post'), [
            'user_id' => $user->id, // Self-assignment attempt
            'role_id' => Role::first()->id,
        ])
        ->assertSessionHasErrors('user_id');
});
```

## JavaScript Testing with Vitest

### Setup

- **Framework**: Vitest with React Testing Library
- **Configuration**: `vitest.config.ts` with jsdom environment
- **Location**: `resources/js/hooks/__tests__/*.test.tsx`

### Example Hook Test

```typescript
import { renderHook } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { useInitials } from '../use-initials';

describe('useInitials', () => {
    it('should return the initials for a standard two-word name', () => {
        const { result } = renderHook(() => useInitials());
        const getInitials = result.current;
        expect(getInitials('John Doe')).toBe('JD');
    });

    it('should handle undefined input gracefully', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current(undefined)).toBe('');
    });
});
```

### JavaScript Test Commands

```bash
# Run JavaScript tests
npm run test:run
npm run test

# Run with UI
npm run test:ui

# Run specific test file
npx vitest resources/js/hooks/__tests__/use-initials.test.tsx
```

## Running Tests

### Commands

```bash
# Run all tests
composer run test
php artisan test --compact

# Run JavaScript tests
npm run test:run

# Run specific test file
php artisan test --compact tests/Feature/DashboardTest.php

# Run specific test method
php artisan test --compact --filter="test_name"

# Run unit tests only
php artisan test tests/Unit

# Run feature tests only
php artisan test tests/Feature
```

### Test Coverage

- Every change must be programmatically tested
- Write both unit tests and feature tests
- Test happy paths, failure paths, and edge cases
- Use descriptive test names with clear expectations

## Mocking & Stubbing

### Mocking with Pest

```php
use function Pest\Laravel\mock;

test('sends notification on password reset', function () {
    Notification::fake();

    // Test logic that triggers notification

    Notification::assertSent(ResetPassword::class);
});
```

### Partial Mocks

```php
$mock = $this->mock(SomeClass::class, function ($mock) {
    $mock->shouldReceive('method')->andReturn('value');
});
```

## Browser Testing (Future)

Pest 4 supports browser testing with Chrome/Chromium:

```php
test('user can reset password', function () {
    $user = User::factory()->create();

    $page = visit('/sign-in')
        ->click('Forgot Password?')
        ->fill('email', $user->email)
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
```

## Test Database Management

- Tests use `testing` database connection
- Automatic database refreshing between tests
- Factories for consistent test data creation
- No data persistence between test runs

## Performance Testing

- Tests should run quickly (< 100ms per test)
- Use `RefreshDatabase` only when necessary
- Mock external services and APIs
- Avoid unnecessary database operations

## Code Coverage

- Track code coverage with `--coverage` flag
- Aim for >80% coverage on critical paths
- Focus on testing business logic over framework code
- Use coverage reports to identify untested code</content>
  <parameter name="filePath">docs/RAG/testing.md
