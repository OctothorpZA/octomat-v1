# Sprint 5: Advanced Authorization Features Implementation Plan

## Overview

Implement advanced authorization features for the Octomat platform MVP, building upon the basic RBAC system from Sprint 2. This sprint adds permission management, bulk operations, club-based role delegation using Laravel pivots, feature flag middleware, enhanced authorization logging, role-based UI toggles, and advanced UI features using React/Inertia.js. **Note: Enterprise features (context-aware permissions, role inheritance, advanced templates) are deferred to Sprint 6 for faster MVP launch.**

## Business Context

The Octomat platform requires advanced authorization features for:

- **Complex Permission Management** - Granular control over system capabilities
- **Club-Based Role Delegation** - Simplified hierarchical management within organizations using Laravel pivots
- **Audit & Compliance** - Complete tracking of role changes for GDPR compliance
- **Bulk Operations** - Efficient management of user roles at scale
- **Enhanced User Experience** - Intuitive permission preview, template management, and role-based UI customization
- **Advanced Analytics** - Role usage statistics and access patterns
- **Feature Management** - Gradual feature rollout via feature flags
- **Enhanced Security** - Comprehensive authorization logging and debugging

## Implementation Strategy

### Phase-Based Approach

- **Phase 1**: Permission Groups & Advanced Models (Database extensions)
- **Phase 2**: Audit Trail System (Role assignment history)
- **Phase 3**: Enhanced UI Features (Templates, bulk operations, previews)
- **Phase 4**: Advanced Authorization (Complex policies, caching)
- **Phase 5**: Analytics & Reporting (Role usage statistics)

### Key Decisions

#### 1. Permission Organization Strategy

- **Decision**: Implement permission groups for better organization
- **Rationale**: Users need logical grouping of permissions for management
- **Implementation**: Group-based permission display and assignment

#### 1.5. Club-Based Role Delegation Strategy

- **Decision**: Use Laravel pivot tables for club-specific role delegation
- **Rationale**: Simpler, proven approach following Laravel conventions
- **Implementation**: `club_user.role` pivot column for club manager/coach/member roles

#### 2. Audit Trail Scope

- **Decision**: Track all role assignments with metadata
- **Rationale**: GDPR compliance and dispute resolution
- **Implementation**: Comprehensive logging with assigned_by, timestamps, reasons

#### 3. Template System Design

- **Decision**: Pre-defined role bundles for common scenarios
- **Rationale**: Streamline onboarding and reduce admin errors
- **Implementation**: Template-based bulk assignment with customization

#### 4. Permission Preview Strategy

- **Decision**: Real-time preview of permission implications
- **Rationale**: Prevent accidental privilege escalation
- **Implementation**: Client-side calculation with server validation

#### 5. Bulk Operation Safety

- **Decision**: Transaction-wrapped bulk operations with rollback
- **Rationale**: Data integrity and error recovery
- **Implementation**: Database transactions with audit logging

---

## Phase 1: Permission Groups & Advanced Models

### 1. Permission Groups Model

```php
// app/Models/PermissionGroup.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
```

### 2. Role Templates Model

```php
// app/Models/RoleTemplate.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'category',
        'description',
        'roles',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'roles' => 'array',
    ];

    public function pivotRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_template_roles');
    }

    // Helper method to get actual Role models
    public function getRoleModels()
    {
        return Role::whereIn('name', $this->roles)->get();
    }
}
```

### 3. Enhanced Club Model with Pivot Relationships

```php
// app/Models/Club.php (additions)
<?php

namespace App\Models;

// ... existing code ...

class Club extends Model
{
    // ... existing code ...

    // Enhanced club membership with roles
    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'assigned_by', 'assigned_at');
    }

    // Get members by role
    public function getMembersByRole(string $role)
    {
        return $this->members()->wherePivot('role', $role)->get();
    }

    // Check if user has club role
    public function userHasRole(User $user, string $role): bool
    {
        return $this->members()->wherePivot('user_id', $user->id)->wherePivot('role', $role)->exists();
    }

    // Assign club role to user
    public function assignRoleToUser(User $user, string $role, ?User $assignedBy = null): void
    {
        $this->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
            ]
        ]);
    }
}
```

### 4. Enhanced Permission Model

```php
// Update app/Models/Permission.php
public function group(): BelongsTo
{
    return $this->belongsTo(PermissionGroup::class, 'group_id');
}

// Scope for active permissions
public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

---

## Phase 2: Database Extensions & Seeders

### 1. Advanced Migration

```php
// database/migrations/xxxx_xx_xx_add_advanced_auth_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permission groups
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add group_id to permissions
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->constrained('permission_groups')->onDelete('set null');
        });

        // Role templates
        Schema::create('role_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->json('roles');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add role column to existing club_user pivot table
        Schema::table('club_user', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id'); // Club-specific role (e.g., 'Club Manager', 'Coach', 'Member')
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null')->after('role');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('role_templates');
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
        Schema::dropIfExists('permission_groups');
    }
};
```

### 2. Permission Groups Seeder

```php
// database/seeders/PermissionGroupSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PermissionGroup;
use App\Models\Permission;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'system',
                'display_name' => 'System Administration',
                'description' => 'Core system management permissions',
                'order' => 1,
                'permissions' => ['system.admin', 'system.audit', 'system.maintenance']
            ],
            [
                'name' => 'users',
                'display_name' => 'User Management',
                'description' => 'User account and profile permissions',
                'order' => 2,
                'permissions' => ['users.manage', 'roles.manage', 'permissions.manage']
            ],
            [
                'name' => 'academies',
                'display_name' => 'Academy Management',
                'description' => 'Academy and organization permissions',
                'order' => 3,
                'permissions' => ['academies.create', 'academies.view', 'academies.manage', 'academies.update', 'clubs.manage', 'club.members.manage', 'club.roles.assign', 'billing.manage']
            ],
            [
                'name' => 'events',
                'display_name' => 'Event Management',
                'description' => 'Competition and event permissions',
                'order' => 4,
                'permissions' => ['events.create', 'events.manage', 'events.update', 'events.delete', 'events.publish', 'events.register', 'competitions.create', 'competitions.manage', 'participants.manage', 'scoring.manage', 'results.publish', 'venues.manage']
            ],
            [
                'name' => 'athletics',
                'display_name' => 'Athletics',
                'description' => 'Athlete and coaching permissions',
                'order' => 5,
                'permissions' => ['athletes.assign', 'athletes.manage', 'athletes.view', 'profile.athlete.manage', 'training.create', 'training.manage', 'results.view', 'schedule.view', 'achievements.view']
            ],
            [
                'name' => 'family',
                'display_name' => 'Family Management',
                'description' => 'Parent and guardian permissions',
                'order' => 6,
                'permissions' => ['family.manage', 'athletes.link', 'children.assign', 'children.view', 'children.manage', 'payments.manage']
            ],
            [
                'name' => 'reports',
                'display_name' => 'Reporting',
                'description' => 'Data and reporting permissions',
                'order' => 7,
                'permissions' => ['reports.view']
            ],
            [
                'name' => 'core',
                'display_name' => 'Core Features',
                'description' => 'Basic platform access permissions',
                'order' => 8,
                'permissions' => ['profile.basic.manage', 'events.view', 'content.consume', 'profile.view', 'notifications.manage']
            ],
        ];

        foreach ($groups as $groupData) {
            $permissions = $groupData['permissions'] ?? [];
            unset($groupData['permissions']);

            $group = PermissionGroup::create($groupData);

            // Assign permissions to this group
            if (!empty($permissions)) {
                Permission::whereIn('name', $permissions)->update(['group_id' => $group->id]);
            }
        }
    }
}
```

### 3. Role Template Seeder

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
                'name' => 'Full Academy Staff',
                'display_name' => 'Academy Owner + Coach',
                'category' => 'academy',
                'description' => 'Complete academy management with coaching capabilities',
                'roles' => ['Academy Owner', 'Coach'],
            ],
            [
                'name' => 'Event Management Team',
                'display_name' => 'Event Organiser + Coach',
                'category' => 'events',
                'description' => 'Full event management with athlete coaching support',
                'roles' => ['Event Organiser', 'Coach'],
            ],
            [
                'name' => 'Family Package',
                'display_name' => 'Parent + Athlete',
                'category' => 'family',
                'description' => 'Parent account with linked athlete profile',
                'roles' => ['Parent/Guardian', 'Athlete'],
            ],
            [
                'name' => 'Tournament Director',
                'display_name' => 'Event Organiser + Academy Owner',
                'category' => 'events',
                'description' => 'Complete tournament management with academy oversight',
                'roles' => ['Event Organiser', 'Academy Owner'],
            ],
        ];

        foreach ($templates as $templateData) {
            RoleTemplate::create($templateData);
        }
    }
}
```

---

## Phase 3: Enhanced UI Features

### 1. Advanced Role Assignment Page (React) with Permission Preview

```tsx
// resources/js/components/BulkAssignmentForm.tsx
import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

export default function BulkAssignmentForm({
    users,
    roles,
    onAssign,
}: {
    users: any[];
    roles: Record<string, string>;
    onAssign: (data: any) => void;
}) {
    const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [expiresAt, setExpiresAt] = useState('');

    const handleBulkAssign = () => {
        if (selectedUsers.length > 10) {
            return alert('Bulk operations limited to 10 users at once');
        }

        if (
            !confirm(
                `Assign ${selectedRoles.length} roles to ${selectedUsers.length} users?`,
            )
        ) {
            return;
        }

        onAssign({
            users: selectedUsers,
            roles: selectedRoles,
            expires_at: expiresAt,
        });
    };

    return (
        <div className="space-y-4">
            <div>
                <Label>Select Users (max 10)</Label>
                <div className="max-h-40 overflow-y-auto rounded border p-2">
                    {users.map((user) => (
                        <div
                            key={user.id}
                            className="flex items-center space-x-2"
                        >
                            <Checkbox
                                id={`user-${user.id}`}
                                checked={selectedUsers.includes(user.id)}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setSelectedUsers([
                                            ...selectedUsers,
                                            user.id,
                                        ]);
                                    } else {
                                        setSelectedUsers(
                                            selectedUsers.filter(
                                                (id) => id !== user.id,
                                            ),
                                        );
                                    }
                                }}
                            />
                            <Label htmlFor={`user-${user.id}`}>
                                {user.full_name || user.email}
                            </Label>
                        </div>
                    ))}
                </div>
            </div>

            <div>
                <Label>Select Roles</Label>
                <div className="max-h-32 overflow-y-auto rounded border p-2">
                    {Object.entries(roles).map(([key, label]) => (
                        <div key={key} className="flex items-center space-x-2">
                            <Checkbox
                                id={`role-${key}`}
                                checked={selectedRoles.includes(key)}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setSelectedRoles([
                                            ...selectedRoles,
                                            key,
                                        ]);
                                    } else {
                                        setSelectedRoles(
                                            selectedRoles.filter(
                                                (role) => role !== key,
                                            ),
                                        );
                                    }
                                }}
                            />
                            <Label htmlFor={`role-${key}`}>{label}</Label>
                        </div>
                    ))}
                </div>
            </div>

            <div>
                <Label htmlFor="expires_at">Expiration Date (optional)</Label>
                <Input
                    id="expires_at"
                    type="datetime-local"
                    value={expiresAt}
                    onChange={(e) => setExpiresAt(e.target.value)}
                />
            </div>

            <Button
                onClick={handleBulkAssign}
                disabled={
                    selectedUsers.length === 0 || selectedRoles.length === 0
                }
            >
                Assign Roles to {selectedUsers.length} Users
            </Button>
        </div>
    );
}
```

```tsx
// resources/js/pages/admin/role-assignment.tsx
import React, { useState } from 'react';
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
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function RoleAssignment({
    users,
    roles,
    templates,
    permissionGroups,
    permissions,
}: {
    users: any[];
    roles: Record<string, string>;
    templates: any[];
    permissionGroups: any[];
    permissions: any[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        selectedUser: '',
        selectedRoles: [] as string[],
        selectedTemplate: '',
    });

    const [showPreview, setShowPreview] = useState(false);
    const [previewPermissions, setPreviewPermissions] = useState<any[]>([]);

    const handleRoleChange = (roleNames: string[]) => {
        setData('selectedRoles', roleNames);
        if (roleNames.length > 0) {
            const rolePermissions = permissions.filter((p) =>
                roleNames.some((roleName) =>
                    p.roles?.some((r: any) => r.name === roleName),
                ),
            );
            setPreviewPermissions(rolePermissions);
            setShowPreview(true);
        } else {
            setPreviewPermissions([]);
            setShowPreview(false);
        }
    };

    const handleTemplateSelect = (templateId: string) => {
        setData('selectedTemplate', templateId);
        const template = templates.find((t) => t.id.toString() === templateId);
        if (template) {
            handleRoleChange(template.roles);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/roles/assign');
    };

    return (
        <>
            <Head title="Advanced Role Assignment" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    Advanced Role Assignment
                </h1>

                <Tabs defaultValue="single" className="w-full">
                    <TabsList>
                        <TabsTrigger value="single">
                            Single Assignment
                        </TabsTrigger>
                        <TabsTrigger value="bulk">Bulk Assignment</TabsTrigger>
                        <TabsTrigger value="templates">Templates</TabsTrigger>
                    </TabsList>

                    <TabsContent value="single" className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Assign Roles to User</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form
                                        onSubmit={handleSubmit}
                                        className="space-y-4"
                                    >
                                        <div>
                                            <Label>Select User</Label>
                                            <Select
                                                value={data.selectedUser}
                                                onValueChange={(value) =>
                                                    setData(
                                                        'selectedUser',
                                                        value,
                                                    )
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
                                                            {user.full_name ||
                                                                user.email}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div>
                                            <Label>
                                                Role Template (Optional)
                                            </Label>
                                            <Select
                                                value={data.selectedTemplate}
                                                onValueChange={
                                                    handleTemplateSelect
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select template or choose roles manually" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {templates.map(
                                                        (template) => (
                                                            <SelectItem
                                                                key={
                                                                    template.id
                                                                }
                                                                value={template.id.toString()}
                                                            >
                                                                {
                                                                    template.display_name
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div>
                                            <Label>Assign Roles</Label>
                                            <div className="max-h-48 space-y-2 overflow-y-auto rounded border p-2">
                                                {Object.entries(roles).map(
                                                    ([key, label]) => (
                                                        <label
                                                            key={key}
                                                            className="flex items-center space-x-2"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={data.selectedRoles.includes(
                                                                    key,
                                                                )}
                                                                onChange={(
                                                                    e,
                                                                ) => {
                                                                    const newRoles =
                                                                        e.target
                                                                            .checked
                                                                            ? [
                                                                                  ...data.selectedRoles,
                                                                                  key,
                                                                              ]
                                                                            : data.selectedRoles.filter(
                                                                                  (
                                                                                      r,
                                                                                  ) =>
                                                                                      r !==
                                                                                      key,
                                                                              );
                                                                    handleRoleChange(
                                                                        newRoles,
                                                                    );
                                                                }}
                                                            />
                                                            <span>{label}</span>
                                                        </label>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Assigning...'
                                                : 'Assign Roles'}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            {showPreview && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Permission Preview
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Alert>
                                            <AlertDescription>
                                                Selected roles will grant the
                                                following permissions:
                                            </AlertDescription>
                                        </Alert>
                                        <div className="mt-4 space-y-4">
                                            {permissionGroups.map((group) => {
                                                const groupPermissions =
                                                    previewPermissions.filter(
                                                        (p) =>
                                                            p.group_id ===
                                                            group.id,
                                                    );
                                                if (
                                                    groupPermissions.length ===
                                                    0
                                                )
                                                    return null;

                                                return (
                                                    <div key={group.id}>
                                                        <h4 className="mb-2 text-sm font-semibold">
                                                            {group.display_name}
                                                        </h4>
                                                        <div className="grid grid-cols-1 gap-1">
                                                            {groupPermissions.map(
                                                                (
                                                                    permission,
                                                                ) => (
                                                                    <Badge
                                                                        key={
                                                                            permission.id
                                                                        }
                                                                        variant="secondary"
                                                                        className="text-xs"
                                                                    >
                                                                        {permission.display_name ||
                                                                            permission.name}
                                                                    </Badge>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </TabsContent>

                    <TabsContent value="bulk">
                        <Card>
                            <CardHeader>
                                <CardTitle>Bulk Role Assignment</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <BulkAssignmentForm
                                    users={users}
                                    roles={roles}
                                    onAssign={(data) =>
                                        post(
                                            '/admin/roles/bulk-assign-roles',
                                            data,
                                        )
                                    }
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="templates">
                        <Card>
                            <CardHeader>
                                <CardTitle>Manage Role Templates</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {/* Template management interface would go here */}
                                <p className="text-muted-foreground">
                                    Template creation and management interface
                                </p>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
```

### 1.5. Role-Based UI Toggles

```php
// Enhanced controller method with role-based UI customization
public function index()
{
    $user = auth()->user();

    return Inertia::render('admin/role-assignment', [
        'users' => User::with('roles')->select('id', 'first_name', 'last_name', 'email')
            ->paginate(15),
        'roles' => Role::where('is_active', true)->pluck('display_name', 'name'),
        'templates' => RoleTemplate::where('is_active', true)->get(),
        'permissionGroups' => PermissionGroup::with('permissions')->active()->ordered()->get(),
        'permissions' => Permission::with('roles')->active()->get(),

        // Role-based UI toggles
        'ui' => [
            'canSelectMultiple' => $user->hasAnyRole(['Super Admin', 'Academy Owner', 'Event Organiser']),
            'canUseTemplates' => $user->hasRole('Super Admin'),
            'canBulkAssign' => $user->hasAnyRole(['Super Admin', 'Academy Owner']),
            'canViewAudit' => $user->hasRole('Super Admin'),
            'canManageRoles' => $user->hasRole('Super Admin'),
            'showAdvancedFeatures' => $user->hasRole('Super Admin'),
        ]
    ]);
}
```

```tsx
// Enhanced React component with role-based UI
export default function RoleAssignment({
    users,
    roles,
    templates,
    permissionGroups,
    permissions,
    ui, // Role-based UI toggles
}: {
    users: any[];
    roles: Record<string, string>;
    templates: any[];
    permissionGroups: any[];
    permissions: any[];
    ui: Record<string, boolean>;
}) {
    const { data, setData, post, processing, errors } = useForm({
        selectedUser: '',
        selectedRoles: [] as string[],
        selectedTemplate: '',
    });

    // UI conditionally rendered based on roles
    return (
        <>
            <Head title="Role Assignment" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Role Assignment</h1>

                <Tabs defaultValue="single">
                    <TabsList>
                        <TabsTrigger value="single">
                            Single Assignment
                        </TabsTrigger>
                        {ui.canBulkAssign && (
                            <TabsTrigger value="bulk">
                                Bulk Assignment
                            </TabsTrigger>
                        )}
                        {ui.canUseTemplates && (
                            <TabsTrigger value="templates">
                                Templates
                            </TabsTrigger>
                        )}
                        {ui.canViewAudit && (
                            <TabsTrigger value="audit">Audit Log</TabsTrigger>
                        )}
                    </TabsList>

                    {/* Existing tabs content */}
                </Tabs>
            </div>
        </>
    );
}
```

### 2. Enhanced Controller with Audit Trail

```php
// app/Http/Controllers/Admin/RoleAssignmentController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\RoleTemplate;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RoleAssignmentController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/role-assignment', [
            'users' => User::with('roles')->select('id', 'first_name', 'last_name', 'email')
                ->paginate(15),
            'roles' => Role::where('is_active', true)->pluck('display_name', 'name'),
            'templates' => RoleTemplate::where('is_active', true)->get(),
            'permissionGroups' => PermissionGroup::with('permissions')->active()->ordered()->get(),
            'permissions' => Permission::with('roles')->active()->get(),
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'string|exists:roles,name',
        ]);

        $user = User::find($validated['selectedUser']);

        DB::transaction(function () use ($user, $validated) {
            // Sync system roles (Spatie)
            $user->syncRoles($validated['selectedRoles']);

            // Clear permission cache
            Cache::forget("user_permissions_{$user->id}");
        });

        return redirect()->back()->with('success', 'Roles assigned successfully!');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'exists:users,id',
            'templateId' => 'required|exists:role_templates,id',
        ]);

        $template = RoleTemplate::find($validated['templateId']);
        $users = User::whereIn('id', $validated['userIds'])->get();
        $roleModels = $template->getRoleModels();

        DB::transaction(function () use ($users, $roleModels, $template) {
            foreach ($users as $user) {
                // Sync template roles
                $user->syncRoles($roleModels);

                // Track bulk assignment
                foreach ($roleModels as $role) {
                    RoleAssignment::create([
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                        'reason' => "Bulk assignment via {$template->name} template",
                        'metadata' => [
                            'template_id' => $template->id,
                            'bulk_operation' => true,
                            'role_count' => $roleModels->count()
                        ],
                    ]);
                }

                // Clear permission cache
                Cache::forget("user_permissions_{$user->id}");
            });
        });

        return redirect()->back()->with('success', 'Bulk assignment completed!');
    }

    public function assignClubRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'club_id' => 'required|exists:clubs,id',
            'role' => 'required|string|in:Club Manager,Coach,Member,Athlete',
        ]);

        $user = User::find($validated['user_id']);
        $club = Club::find($validated['club_id']);

        // Check permissions (club owner or super admin)
        if (!$request->user()->isClubOwner($club) && !$request->user()->hasRole('Super Admin')) {
            return back()->withErrors(['authorization' => 'Insufficient permissions']);
        }

        $club->assignRoleToUser($user, $validated['role'], $request->user());

        return back()->with('success', 'Club role assigned successfully!');
    }

    public function auditLog(Request $request)
    {
        return Inertia::render('admin/audit-log', [
            'assignments' => RoleAssignment::with(['user', 'role', 'assignedBy'])
                ->latest('assigned_at')
                ->paginate(50),
        ]);
    }
}
```

---

## Phase 4: Advanced Authorization Features

### 1. Enhanced Middleware with Feature Flags

```php
// app/Http/Middleware/EnsureRoleAccess.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleAccess
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check for feature flags (parameters starting with 'feature:')
        $features = array_filter($roles, fn($role) => str_starts_with($role, 'feature:'));
        $roles = array_filter($roles, fn($role) => !str_starts_with($role, 'feature:'));

        // Check roles
        $hasRequiredRole = empty($roles) || $user->hasAnyRole($roles);

        // Check feature flags
        $featuresMet = true;
        if (!empty($features)) {
            foreach ($features as $feature) {
                $featureName = str_replace('feature:', '', $feature);
                if (!config("features.{$featureName}", false)) {
                    $featuresMet = false;
                    break;
                }
            }
        }

        if (!$hasRequiredRole || !$featuresMet) {
            return response()->json([
                'error' => 'Access denied',
                'required_roles' => $roles,
                'missing_features' => $featuresMet ? [] : $features
            ], 403);
        }

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/RestrictRouteByRole.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestrictRouteByRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            Log::info('Route access denied: No authenticated user', [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hasAccess = $user->hasAnyRole($roles);

        Log::info('Role-based route access check', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'required_roles' => $roles,
            'access_granted' => $hasAccess,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        if (!$hasAccess) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
```

### 2. Enhanced Policies

```php
// app/Policies/AcademyPolicy.php
<?php

namespace App\Policies;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AcademyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('academies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('academies.create');
    }

    public function update(User $user, Academy $academy): bool
    {
        // Owner can always manage their academy
        if ($academy->owner_id === $user->id) {
            return Response::allow();
        }

        // Check specific permission
        if (!$user->hasPermissionTo('academies.update')) {
            return Response::deny('You do not have permission to update academies.');
        }

        return Response::allow();
    }

    public function delete(User $user, Academy $academy): bool
    {
        return $user->hasPermissionTo('academies.delete') ||
               $academy->owner_id === $user->id;
    }

    public function manageMembers(User $user, Academy $academy): bool
    {
        return $academy->owner_id === $user->id ||
               $this->isClubManager($user, $academy) ||
                ($user->hasPermissionTo('academies.manage') &&
                 $this->update($user, $academy));
    }

    protected function isClubManager(User $user, Academy $academy): bool
    {
        return $user->clubMemberships()
            ->where('club_id', $academy->id)
            ->wherePivot('role', 'Club Manager')
            ->exists();
    }
}
```

### 2. Permission Caching Listener

```php
// app/Providers/AppServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Events\RoleAssigned;
use Spatie\Permission\Events\RoleRevoked;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Clear permission cache when roles change
        Event::listen(RoleAssigned::class, function (RoleAssigned $event) {
            Cache::forget("user_permissions_{$event->model->id}");
        });

        Event::listen(RoleRevoked::class, function (RoleRevoked $event) {
            Cache::forget("user_permissions_{$event->model->id}");
        });
    }
}
```

### 3. Advanced Permission Checking Hook

```tsx
// resources/js/hooks/usePermissions.ts
import { usePage } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage().props;

    const hasPermission = (permission: string) => {
        return auth.permissions?.includes(permission) ?? false;
    };

    const hasRole = (role: string) => {
        return auth.roles?.includes(role) ?? false;
    };

    const hasAnyRole = (roles: string[]) => {
        return roles.some((role) => hasRole(role));
    };

    const hasAllRoles = (roles: string[]) => {
        return roles.every((role) => hasRole(role));
    };

    const hasAnyPermission = (permissions: string[]) => {
        return permissions.some((permission) => hasPermission(permission));
    };

    const hasAllPermissions = (permissions: string[]) => {
        return permissions.every((permission) => hasPermission(permission));
    };

    const canAccess = (
        requiredPermissions: string[],
        requiredRoles: string[] = [],
    ) => {
        const hasRequiredPermissions =
            requiredPermissions.length === 0 ||
            hasAnyPermission(requiredPermissions);
        const hasRequiredRoles =
            requiredRoles.length === 0 || hasAnyRole(requiredRoles);

        return hasRequiredPermissions && hasRequiredRoles;
    };

    return {
        hasPermission,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        hasAnyPermission,
        hasAllPermissions,
        canAccess,
        permissions: auth.permissions ?? [],
        roles: auth.roles ?? [],
    };
}
```

---

## Phase 5: Analytics & Reporting

### 1. Comprehensive Role Analytics Dashboard

```tsx
// resources/js/pages/admin/role-analytics.tsx
import React from 'react';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export default function RoleAnalytics({ analytics }: { analytics: any }) {
    return (
        <>
            <Head title="Role Analytics" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    Role Analytics & Reporting
                </h1>

                {/* Key Metrics */}
                <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {analytics.totalUsers}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Registered users
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Active Roles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {analytics.activeRoles}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Available roles
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Role Assignments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {analytics.totalAssignments}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Total assignments
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {analytics.auditEvents}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Last 30 days
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Role Distribution Chart */}
                <div className="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Distribution</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Assignments</TableHead>
                                        <TableHead>Percentage</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {analytics.roleDistribution.map(
                                        (item: any) => (
                                            <TableRow key={item.name}>
                                                <TableCell className="font-medium">
                                                    {item.name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.count}
                                                </TableCell>
                                                <TableCell>
                                                    {(
                                                        (item.count /
                                                            analytics.totalAssignments) *
                                                        100
                                                    ).toFixed(1)}
                                                    %
                                                </TableCell>
                                            </TableRow>
                                        ),
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {analytics.recentActivity
                                    .slice(0, 5)
                                    .map((activity: any) => (
                                        <div
                                            key={activity.id}
                                            className="flex items-center justify-between rounded border p-3"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {activity.user?.full_name ||
                                                        'Unknown User'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Assigned{' '}
                                                    {
                                                        activity.role
                                                            ?.display_name
                                                    }
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {activity.assigned_by
                                                        ? 'Admin'
                                                        : 'System'}
                                                </Badge>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {new Date(
                                                        activity.assigned_at,
                                                    ).toLocaleDateString()}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Audit Log Section */}
                <Card>
                    <CardHeader>
                        <CardTitle>Complete Audit Log</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="mb-4 text-muted-foreground">
                            Full audit trail available in the Audit Log section
                            with advanced filtering and search capabilities.
                        </p>
                        <div className="text-center">
                            <a
                                href="/admin/audit-log"
                                className="inline-flex items-center rounded-md bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90"
                            >
                                View Complete Audit Log
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

### 2. Audit Log Interface

```tsx
// resources/js/pages/admin/audit-log.tsx
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export default function AuditLog({
    assignments,
    filters,
    users,
    roles,
}: {
    assignments: any;
    filters: any;
    users: any[];
    roles: any[];
}) {
    const { data, setData, get, processing } = useForm(filters);

    const handleFilter = () => {
        get('/admin/audit-log');
    };

    return (
        <>
            <Head title="Role Audit Log" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    Role Assignment Audit Log
                </h1>

                {/* Filters */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Filter Audit Log</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <Label>User</Label>
                                <Select
                                    value={data.user_id || ''}
                                    onValueChange={(value) =>
                                        setData('user_id', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All users" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.first_name}{' '}
                                                {user.last_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Role</Label>
                                <Select
                                    value={data.role_id || ''}
                                    onValueChange={(value) =>
                                        setData('role_id', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem
                                                key={role.id}
                                                value={role.id.toString()}
                                            >
                                                {role.display_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Assigned By</Label>
                                <Select
                                    value={data.assigned_by || ''}
                                    onValueChange={(value) =>
                                        setData('assigned_by', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All assigners" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.first_name}{' '}
                                                {user.last_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button
                                    onClick={handleFilter}
                                    disabled={processing}
                                >
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Audit Log Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Audit Log ({assignments.total} entries)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Assigned By</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Reason</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assignments.data.map((assignment: any) => (
                                    <TableRow key={assignment.id}>
                                        <TableCell>
                                            {assignment.user?.full_name ||
                                                'Unknown'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {assignment.role?.display_name}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {assignment.assigned_by
                                                ? assignment.assignedBy
                                                      ?.full_name || 'Admin'
                                                : 'System'}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(
                                                assignment.assigned_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate">
                                            {assignment.reason}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Pagination would go here */}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

---

## Success Criteria

### **Advanced Features**

- [ ] Permission groups implemented and working
- [ ] Role templates created and functional
- [ ] Audit trail tracking all role changes
- [ ] Permission preview showing grouped permissions
- [ ] Bulk assignment working with templates
- [ ] Advanced caching implemented
- [ ] Frontend permission consistency maintained
- [ ] Role conflict resolution working
- [ ] Enhanced policies with detailed responses
- [ ] Feature flag middleware implemented
- [ ] Enhanced authorization logging working
- [ ] Role-based UI toggles functional
- [ ] Club-based role delegation system implemented (pivot-based)
- [ ] Club ownership and hierarchical permissions working
- [ ] Club-specific roles managed via pivot relationships

### **Analytics & Reporting**

- [ ] Role usage statistics available
- [ ] Audit log interface implemented
- [ ] Permission analytics dashboard
- [ ] GDPR compliance features working

### **Performance & Security**

- [ ] Permission caching reducing database queries
- [ ] Transaction safety for bulk operations
- [ ] Input validation preventing security issues
- [ ] Audit trail preventing unauthorized changes

---

## Advanced Testing

### 1. Multi-Role Assignment Test

```php
// tests/Feature/RoleTest.php (enhanced for Sprint 5)
test('multi-role assignment works', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRoles' => ['Coach', 'Parent/Guardian']
        ])
        ->assertRedirect();

    expect($user->hasRole('Coach'))->toBeTrue();
    expect($user->hasRole('Parent/Guardian'))->toBeTrue();
});

test('expired roles are automatically revoked', function () {
    $user = User::factory()->create();
    $user->assignRole('Coach');

    // Simulate expired assignment
    $assignment = $user->roles()->first()->pivot;
    $assignment->update(['expires_at' => now()->subDay()]);

    // Run expiry job (mock or actual)
    RevokeExpiredRoles::dispatch();

    $user->refresh();
    expect($user->hasRole('Coach'))->toBeFalse();
});

test('bulk assignment handles partial failures', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $validUser = User::factory()->create();
    $invalidUserId = 99999; // Non-existent

    $this->actingAs($admin)
        ->post('/admin/roles/bulk-assign-roles', [
            'users' => [$validUser->id, $invalidUserId],
            'roles' => ['Coach']
        ])
        ->assertRedirect()
        ->assertSessionHas('message', 'Assigned to 1 users. Failed for user 99999: User not found');

    expect($validUser->hasRole('Coach'))->toBeTrue();
});

test('permission cache prevents unauthorized access', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');

    // Mock cache poisoning attempt
    Cache::put("user_permissions_{$user->id}", ['system.admin']);

    // Should use actual permissions, not cached
    expect($user->hasPermissionTo('system.admin'))->toBeFalse();
});

test('club owners have full control over their clubs', function () {
    $owner = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $owner->id]);
    $manager = User::factory()->create();

    // Owner should have full access
    $policy = new ClubPolicy();
    expect($policy->update($owner, $club))->toBeTrue();

    // Non-owner should not
    $otherUser = User::factory()->create();
    expect($policy->update($otherUser, $club))->toBeFalse();
});

test('club managers can manage members in their club', function () {
    $owner = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $owner->id]);
    $manager = User::factory()->create();

    // Assign club manager role
    $club->members()->attach($manager, ['role' => 'Club Manager']);

    $policy = new ClubPolicy();
    expect($policy->manageMembers($manager, $club))->toBeTrue();
});

test('club-specific role assignments work with pivot', function () {
    $owner = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $owner->id]);
    $member = User::factory()->create();

    // Assign club role via pivot
    $club->assignRoleToUser($member, 'Club Manager', $owner);

    expect($club->userHasRole($member, 'Club Manager'))->toBeTrue();
    expect($member->isClubManager($club))->toBeTrue();
});

test('club owners can delegate management to members', function () {
    $owner = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $owner->id]);
    $manager = User::factory()->create();

    // Owner assigns club manager role
    $club->assignRoleToUser($manager, 'Club Manager', $owner);

    // Manager should be able to manage club members
    $policy = new ClubPolicy();
    expect($policy->manageMembers($manager, $club))->toBeTrue();
});
```

---

## Implementation Order (Week-by-Week Breakdown)

### **Week 1: Foundation & Models**

**Days 1-2: Database Extensions**

- Create permission groups and audit tables
- Add role column to club_user pivot table for simplified club delegation
- Run migrations and test schema

**Days 3-4: Models & Seeders**

- Create advanced models with relationships
- Implement seeders with proper data
- Test model relationships work

**Days 5: Basic Controller Updates**

- Update controllers to use new models
- Add audit trail logging
- Test basic functionality

### **Week 2: UI Enhancements**

**Days 6-7: Advanced React Components**

- Implement permission preview
- Add template selection
- Create bulk assignment interface

**Days 8-9: Controller Enhancements**

- Add bulk operations with transactions
- Implement proper error handling
- Test all controller methods

**Days 10: UI Polish & Testing**

- Polish React components
- Add comprehensive tests
- Test integration flows

### **Week 3: Advanced Features**

**Days 11-12: Caching & Performance**

- Implement permission caching
- Add cache invalidation listeners
- Test performance improvements

**Days 13-14: Policies & Security**

- Implement comprehensive policies with club ownership checks
- Add club-based role delegation logic
- Add security validations
- Implement feature flag middleware
- Add enhanced authorization logging
- Test authorization flows and club-specific permissions

**Days 15: Analytics & Reporting**

- Create analytics dashboard
- Implement audit log interface
- Add reporting features

---

## Files to Create/Modify

### New Models

- `app/Models/PermissionGroup.php`
- `app/Models/RoleTemplate.php`
- `app/Models/RoleAssignment.php`

### Modified Models

- `app/Models/Permission.php` (add group relationship)
- `app/Models/User.php` (enhanced methods + club ownership)

    ```php
    // Add to existing User.php
    public function ownedClub()
    {
        return $this->hasOne(Club::class);
    }

    public function clubMemberships()
    {
        return $this->belongsToMany(Club::class)->withPivot('role', 'assigned_by', 'assigned_at');
    }

    public function isClubOwner(Club $club): bool
    {
        return $this->ownedClub?->id === $club->id;
    }

    public function isClubManager(Club $club): bool
    {
        return $this->clubMemberships()
            ->where('club_id', $club->id)
            ->wherePivot('role', 'Club Manager')
            ->exists();
    }

    // Get clubs where user has specific role
    public function clubsWithRole(string $role)
    {
        return $this->clubMemberships()->wherePivot('role', $role)->get();
    }
    ```

- Enhanced `app/Models/Club.php` (pivot-based club roles)
- Enhanced `app/Models/User.php` (club relationships)
- Enhanced `app/Http/Controllers/Admin/RoleAssignmentController.php`
- New `app/Http/Controllers/Admin/RoleAnalyticsController.php`
- New `app/Http/Middleware/EnsureRoleAccess.php` (feature flags)
- New `app/Http/Middleware/RestrictRouteByRole.php` (enhanced logging)

### React Components

- Enhanced `resources/js/pages/admin/role-assignment.tsx`
- New `resources/js/pages/admin/role-analytics.tsx`
- New `resources/js/components/BulkAssignmentForm.tsx`
- New `resources/js/pages/admin/audit-log.tsx`
- Enhanced `resources/js/hooks/usePermissions.ts`

### Database

- `database/migrations/xxxx_xx_xx_add_advanced_auth_tables.php`
- `database/seeders/PermissionGroupSeeder.php`
- `database/seeders/RoleTemplateSeeder.php`

### Tests

- Enhanced `tests/Feature/RoleTest.php`
- New `tests/Feature/RoleAnalyticsTest.php`
- Enhanced React component tests

### Configuration

- Enhanced `config/permission.php` (caching)
- Enhanced `app/Providers/AppServiceProvider.php` (listeners)
- Enhanced `app/Http/Middleware/HandleInertiaRequests.php` (cache security)

---

**Sprint Priority**: MEDIUM
**Estimated Effort**: 3 weeks (15 days)
**Dependencies**: Sprint 2 completion
**Business Value**: Advanced admin tools, compliance, scalability
