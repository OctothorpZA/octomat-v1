# Sprint 2: Roles & Permissions MVP Implementation Plan

## Overview

Implement a minimal viable role-based authorization system for the Octomat platform, building upon the user identity foundation from Sprint 1. This sprint establishes the core authorization infrastructure needed for basic access control using React/Inertia.js instead of Livewire. Includes 11 scalable roles with clean 100-point hierarchy, comprehensive permissions, and enterprise-ready testing.

## Business Context

The Octomat platform requires basic role-based access control for:

- **System Administration** - Platform administrators need full system access
- **Basic User Management** - Different access levels for users
- **Route Protection** - Protecting sensitive areas of the application

## Implementation Strategy

### Phase-Based Approach

- **Phase 1**: Foundation Setup (Spatie package, database)
- **Phase 2**: Core Role Implementation (Roles, permissions, assignment)
- **Phase 3**: Basic UI Development (Simple role management)
- **Phase 4**: Authorization Integration (Middleware, gates)
- **Phase 5**: Testing & Documentation (Essential test coverage)

### Key Decisions

#### 1. Minimal Viable Scope

- **Decision**: Focus on core RBAC functionality only
- **Rationale**: Get working authorization quickly without over-engineering
- **Trade-off**: Defer advanced features to future sprints

#### 2. Role Hierarchy Strategy

- **Decision**: Simple numeric level system (1000, 950, 900, 600, 500, 400, 300)
- **Rationale**: Basic hierarchy for UI rendering
- **Implementation**: Highest level takes precedence

#### 3. Permission Scope

- **Decision**: Basic permissions per role
- **Rationale**: Start simple, expand later
- **Implementation**: Module-based permission names

---

## Phase 1: Foundation Setup

### 1. Package Installation

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

# Ensure critical roles exist
php artisan tinker --execute="
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'General User']);
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
"
```

### 2. Extend Spatie Tables

```php
// database/migrations/xxxx_xx_xx_extend_spatie_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend roles table with custom fields
        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->integer('level')->default(300);
            $table->string('module')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // Extend permissions table with custom fields
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'level', 'module', 'is_active']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'is_active']);
        });
    }
};
```

### 3. User Model Extension

```php
// Add to existing app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles; // DO NOT override this method

    // Custom helper methods
    public function getHighestRoleLevel(): int
    {
        return $this->roles()->max('level') ?? 300;
    }

    public function getPrimaryRole(): ?Role
    {
        return $this->roles()
            ->orderByDesc('level')
            ->first();
    }

    // Auto-assign default role
    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Guard against seeder not running yet (safer approach)
            if (!$user->roles()->exists()
                && \Spatie\Permission\Models\Role::where('name', 'General User')->exists()) {
                $user->assignRole('General User');
            }
        });
    }

    // Add fallback role creation to prevent roleless users
    protected function assignDefaultRole(): void
    {
        if (!$this->roles()->exists()) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'General User']);
            $this->assignRole($role);
        }
    }
}
```

---

## Phase 2: Core Role Implementation

### 1. Role Seeder with Permissions

```php
// database/seeders/RoleSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create basic permissions
        $permissions = [
            'system.admin', 'system.audit',
            'users.manage', 'roles.manage',
            'federation.admin', 'federation.members.manage',
            'academies.create', 'academies.view', 'academies.manage',
            'clubs.manage', 'club.members.manage',
            'events.create', 'events.view', 'events.manage',
            'profile.basic.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            [
                'name' => 'Super Admin',
                'display_name' => 'System Administrator',
                'level' => 1000,
                'module' => 'system',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Federation Admin',
                'display_name' => 'Federation Administrator',
                'level' => 900,
                'module' => 'federation',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Event Organiser',
                'display_name' => 'Event Organiser',
                'level' => 800,
                'module' => 'events',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Affiliate Manager',
                'display_name' => 'Affiliate Manager',
                'level' => 750,
                'module' => 'federation',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Academy Owner',
                'display_name' => 'Academy Owner',
                'level' => 700,
                'module' => 'academy',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Club Manager',
                'display_name' => 'Club Manager',
                'level' => 600,
                'module' => 'club',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Club Admin',
                'display_name' => 'Club Administrator',
                'level' => 500,
                'module' => 'club',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Coach',
                'display_name' => 'Coach',
                'level' => 400,
                'module' => 'athletics',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Event Staff',
                'display_name' => 'Event Staff',
                'level' => 300,
                'module' => 'events',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Parent/Guardian',
                'display_name' => 'Parent/Guardian',
                'level' => 250,
                'module' => 'family',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Athlete',
                'display_name' => 'Athlete',
                'level' => 200,
                'module' => 'athletics',
                'guard_name' => 'web'
            ],
            [
                'name' => 'General User',
                'display_name' => 'General User',
                'level' => 100,
                'module' => 'core',
                'guard_name' => 'web'
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']], $roleData);

            // Assign basic permissions based on role
            $rolePermissions = match($roleData['name']) {
                'Super Admin' => ['system.admin', 'users.manage', 'roles.manage'],
                'Federation Admin' => ['system.audit', 'users.manage', 'federation.admin'],
                'Event Organiser' => ['events.create', 'events.view', 'events.manage'],
                'Affiliate Manager' => ['academies.view', 'federation.members.manage'],
                'Academy Owner' => ['academies.create', 'academies.view', 'academies.manage'],
                'Club Manager' => ['academies.view', 'clubs.manage', 'club.members.manage'],
                'Club Admin' => ['clubs.manage', 'club.members.manage'],
                'Coach' => ['events.view'],
                'Event Staff' => ['events.view'],
                'General User' => ['profile.basic.manage'],
                default => []
            };

            if (!empty($rolePermissions)) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }
}
```

### 2. Role Templates (Optional Enhancement)

```php
// database/seeders/RoleTemplateSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoleTemplate;

class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'academy_owner_template',
                'display_name' => 'Academy Owner',
                'description' => 'Full academy management permissions',
                'roles' => ['Academy Owner'],
                'category' => 'management',
            ],
            [
                'name' => 'club_manager_template',
                'display_name' => 'Club Manager',
                'description' => 'Club leadership and management',
                'roles' => ['Club Manager'],
                'category' => 'management',
            ],
            [
                'name' => 'coach_template',
                'display_name' => 'Coach',
                'description' => 'Training and athlete management',
                'roles' => ['Coach'],
                'category' => 'coaching',
            ],
            [
                'name' => 'athlete_template',
                'display_name' => 'Athlete',
                'description' => 'Competition participation',
                'roles' => ['Athlete'],
                'category' => 'participant',
            ],
            [
                'name' => 'event_staff_template',
                'display_name' => 'Event Staff',
                'description' => 'Event operations support',
                'roles' => ['Event Staff'],
                'category' => 'operations',
            ],
        ];

        foreach ($templates as $template) {
            RoleTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
```

### 3. Advanced Validation Rules

```php
// Enhanced validation in RoleAssignmentController
public function assign(Request $request)
{
    $validated = $request->validate([
        'selectedUser' => 'required|exists:users,id',
        'selectedRole' => 'required|string|exists:roles,name',
    ]);

    // Advanced validation rules
    $user = User::find($validated['selectedUser']);
    $role = Role::where('name', $validated['selectedRole'])->first();

    // Prevent self-assignment of admin roles
    if ($request->user()->id === $user->id && $role->level >= 800) {
        return back()->withErrors(['authorization' => 'Cannot assign high-level roles to yourself']);
    }

    // Check role level hierarchy (assignee cannot assign higher-level roles)
    if ($request->user()->getHighestRoleLevel() <= $role->level) {
        return back()->withErrors(['authorization' => 'Cannot assign roles at or above your level']);
    }

    // Check for existing conflicting roles
    $conflictingRoles = $user->roles->filter(function ($existingRole) use ($role) {
        return abs($existingRole->level - $role->level) < 100; // Prevent similar level assignments
    });

    if ($conflictingRoles->isNotEmpty()) {
        return back()->withErrors(['conflict' => 'User already has roles at similar authority level']);
    }

    // Existing assignment logic...
}
```

### 4. Update DatabaseSeeder

```php
// Add to existing DatabaseSeeder.php
public function run(): void
{
    // ... existing seeder code ...

    $this->call([
        RoleSeeder::class,
    ]);
}
```

---

## Phase 3: Basic UI Development

### 1. Simple Role Assignment Page (React)

```tsx
// resources/js/pages/admin/role-assignment.tsx
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';

export default function RoleAssignment({
    users,
    roles,
}: {
    users: any[];
    roles: Record<string, string>;
}) {
    const { data, setData, post, processing, errors } = useForm({
        selectedUser: '',
        selectedRole: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/roles/assign');
    };

    return (
        <>
            <Head title="Role Assignment" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Role Assignment</h1>

                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Assign Role to User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label>Select User</Label>
                                <Select
                                    value={data.selectedUser}
                                    onValueChange={(value) =>
                                        setData('selectedUser', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose User" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.full_name || user.email}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.selectedUser && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedUser}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Assign Role</Label>
                                <Select
                                    value={data.selectedRole}
                                    onValueChange={(value) =>
                                        setData('selectedRole', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(roles).map(
                                            ([key, label]) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.selectedRole && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedRole}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Assigning...' : 'Assign Role'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

### 2. Role Assignment Controller

```php
// app/Http/Controllers/Admin/RoleAssignmentController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleAssignmentController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/role-assignment', [
            'users' => User::select('id', 'first_name', 'last_name', 'email')
                ->get(),
            'roles' => [
                'Super Admin' => 'System Administrator',
                'Federation Admin' => 'Federation Administrator',
                'Event Organiser' => 'Event Organiser',
                'Affiliate Manager' => 'Affiliate Manager',
                'Academy Owner' => 'Academy Owner',
                'Club Manager' => 'Club Manager',
                'Club Admin' => 'Club Administrator',
                'Coach' => 'Coach',
                'Parent/Guardian' => 'Parent/Guardian',
                'Athlete' => 'Athlete',
                'Event Staff' => 'Event Staff',
                'General User' => 'General User',
            ],
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string',
        ]);

        // Add: Prevent privilege escalation
        if (!$request->user()->hasRole('Super Admin')) {
            return back()->withErrors(['authorization' => 'Insufficient permissions']);
        }

        $user = User::find($validated['selectedUser']);
        $user->syncRoles([$validated['selectedRole']]);

        return redirect()->back()->with('success', 'Role assigned successfully!');
    }
}
```

### 3. Routes

```php
// routes/web.php
use App\Http\Controllers\Admin\RoleAssignmentController;

Route::middleware(['auth', 'role:Super Admin'])->prefix('admin')->group(function () {
    Route::get('/roles/assign', [RoleAssignmentController::class, 'index'])
        ->name('admin.roles.assign');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])
        ->name('admin.roles.assign.post');
});
```

### 4. Role-Based UI Rendering

```tsx
// Example component with role-based rendering
import { usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth } = usePage().props;
    const roles = auth.roles;

    const hasRole = (role: string) => roles?.includes(role);

    return (
        <div>
            {hasRole('Super Admin') && (
                <div className="admin-panel">
                    <h2>Admin Dashboard</h2>
                    {/* Admin content */}
                </div>
            )}

            {hasRole('Academy Owner') && (
                <div className="academy-panel">
                    <h2>Academy Dashboard</h2>
                    {/* Academy owner content */}
                </div>
            )}

            {/* Default content for all users */}
            <div className="user-dashboard">
                <h2>Welcome, {auth.user.full_name}!</h2>
                {/* General user content */}
            </div>
        </div>
    );
}
```

---

## Phase 4: Authorization Integration

### 1. Middleware Registration

```php
// bootstrap/app.php
use Spatie\Permission\Middlewares\RoleMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => RoleMiddleware::class,
    ]);
})
```

### 2. Protected Routes

```php
// routes/web.php
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware(['auth', 'role:Academy Owner'])->group(function () {
    Route::get('/academy', [AcademyController::class, 'index']);
});

Route::middleware(['auth', 'role:Event Organiser'])->group(function () {
    Route::get('/events', [EventController::class, 'index']);
});
```

### 3. Basic Gates

```php
// app/Providers/AuthServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-academies', function ($user) {
            return $user->hasRole('Academy Owner');
        });

        Gate::define('manage-events', function ($user) {
            return $user->hasRole('Event Organiser');
        });

        Gate::define('system-admin', function ($user) {
            return $user->hasRole('Super Admin');
        });
    }
}
```

### 4. Share Roles in Inertia

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'roles' => $request->user() ? $request->user()->getRoleNames() : [],
        ],
    ]);
}
```

---

## Phase 5: Testing & Documentation

### 1. Basic Role Tests

```php
// tests/Feature/RoleTest.php
test('super admin can assign roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach'
        ])
        ->assertRedirect();

    expect($user->hasRole('Coach'))->toBeTrue();
});

test('users get default role on registration', function () {
    $user = User::factory()->create();

    expect($user->hasRole('General User'))->toBeTrue();
});

test('role-based middleware protects routes', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');

    $this->actingAs($user)
        ->get('/admin/roles/assign')
        ->assertForbidden();
});

test('role hierarchy works', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');
    $user->assignRole('Coach');

    expect($user->getHighestRoleLevel())->toBe(600);
    expect($user->getPrimaryRole()->name)->toBe('Coach');
});

test('role level hierarchy works', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');
    $user->assignRole('Coach');

    expect($user->getHighestRoleLevel())->toBe(600); // Coach level
    expect($user->getPrimaryRole()->name)->toBe('Coach');
});

test('default role fallback works when role missing', function () {
    // Delete General User role if exists
    \Spatie\Permission\Models\Role::where('name', 'General User')->delete();

    $user = User::factory()->create();
    expect($user->hasRole('General User'))->toBeTrue();
});

test('non-super admin cannot assign super admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Academy Owner'); // Not Super Admin

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Super Admin'
        ])
        ->assertForbidden();
});
```

### 2. React Component Tests

```tsx
// tests/js/components/RoleAssignment.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import RoleAssignment from '@/pages/admin/role-assignment';

const mockUsers = [
    { id: 1, full_name: 'John Doe', email: 'john@example.com' },
    { id: 2, full_name: 'Jane Smith', email: 'jane@example.com' },
];

const mockRoles = {
    'Super Admin': 'System Administrator',
    Coach: 'Coach',
};

test('renders role assignment form', () => {
    render(<RoleAssignment users={mockUsers} roles={mockRoles} />);

    expect(screen.getByText('Role Assignment')).toBeInTheDocument();
    expect(screen.getByText('Assign Role to User')).toBeInTheDocument();
});

test('can select user and role', () => {
    render(<RoleAssignment users={mockUsers} roles={mockRoles} />);

    const userSelect = screen.getByLabelText('Select User');
    const roleSelect = screen.getByLabelText('Assign Role');

    fireEvent.click(userSelect);
    fireEvent.click(screen.getByText('John Doe'));

    fireEvent.click(roleSelect);
    fireEvent.click(screen.getByText('Coach'));

    expect(userSelect).toHaveValue('1');
    expect(roleSelect).toHaveValue('Coach');
});
```

---

## Success Criteria

### **Core Functionality**

- [ ] Spatie Laravel Permission installed and configured
- [ ] Database tables extended with custom fields
- [ ] All 11 core roles created and seeded with basic permissions
- [ ] Role templates created for common user types
- [ ] Advanced validation rules preventing privilege escalation
- [ ] Role hierarchy guide documented for developers
- [ ] Permission matrix mapping roles to capabilities
- [ ] Integration tests covering full user journeys
- [ ] Security tests preventing privilege escalation
- [ ] Performance tests for role checking under load
- [ ] User model properly extended with HasRoles trait
- [ ] Default role assignment working on user registration
- [ ] Simple React UI for role assignment working
- [ ] Routes protected with role middleware
- [ ] Role-based UI rendering working in React
- [ ] Basic authorization gates implemented
- [ ] Roles shared in Inertia for frontend access

### **Testing & Quality**

- [ ] Basic test coverage for role assignment
- [ ] React component tests passing
- [ ] Authorization middleware tests passing
- [ ] Role hierarchy tests working

---

## Implementation Order (Day-by-Day Breakdown)

### **Phase 1: Foundation Setup (Days 1-2)**

**Day 1: Package Installation**

- Install Spatie Laravel Permission package
- Publish and run migrations
- Extend tables with custom fields

**Day 2: User Model & Seeding**

- Extend User model with HasRoles trait
- Create RoleSeeder with basic permissions
- Test role assignment works

### **Phase 2: Basic UI Development (Days 3-4)**

**Day 3: Role Assignment Interface**

- Create simple React component
- Implement Inertia form handling
- Add basic validation

**Day 4: Controller & Routes**

- Create RoleAssignmentController
- Set up protected routes with middleware
- Test role-based access control

### **Phase 3: Authorization Integration (Days 5-6)**

**Day 5: Middleware & Gates**

- Register Spatie middleware
- Create basic authorization gates
- Share roles in Inertia

**Day 6: Testing & Polish**

- Write basic tests
- Test authorization flows
- Update documentation

---

## Files to Create

### Models

- Extended `app/Models/User.php` (HasRoles trait)

### Controllers

- `app/Http/Controllers/Admin/RoleAssignmentController.php`

### React Components

- `resources/js/pages/admin/role-assignment.tsx`

### Database

- `database/migrations/xxxx_xx_xx_extend_spatie_tables.php`
- `database/seeders/RoleSeeder.php`

### Tests

- `tests/Feature/RoleTest.php`
- `tests/js/pages/admin/RoleAssignment.test.tsx`

### Configuration

- `bootstrap/app.php` (middleware)
- `app/Providers/AuthServiceProvider.php` (gates)
- `app/Http/Middleware/HandleInertiaRequests.php` (share roles)

---

## Key Adaptations for React/Inertia.js

### From Livewire to React:

- **Components**: Livewire components → React functional components with hooks
- **Forms**: Livewire wire:model → Inertia useForm hook
- **Routing**: Livewire Route::livewire → Inertia Route::get with controller
- **UI Rendering**: Blade @role directives → React conditional rendering with auth.user.roles
- **State Management**: Livewire reactive properties → React useState/useForm hooks
- **Server Communication**: Livewire actions → Inertia form submissions and redirects

### Benefits of React Approach:

- **Modern Frontend**: TypeScript support, better testing with Vitest
- **Component Reusability**: Share role-based components across the application
- **Better UX**: Client-side validation and optimistic updates
- **Scalability**: Easier to integrate with complex state management

---

**Sprint Priority**: HIGH
**Estimated Effort**: 1 week (6 days)
**Dependencies**: Sprint 1 completion
**Technology Stack**: Laravel 12 + React 19 + Inertia.js v2

**Post-Sprint**: Ready for Sprint 5 advanced authorization features
