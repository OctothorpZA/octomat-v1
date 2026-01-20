# Sprint 6: Enterprise Authorization Enhancements

## Overview

Implement enterprise-grade authorization features for the Octomat platform, building upon the MVP RBAC system from Sprint 5. This sprint adds sophisticated context-aware permissions, role inheritance, advanced templates, dynamic navigation, and federation support using patterns from the original prototype.

## Business Context

The Octomat platform requires enterprise authorization features for:

- **Context-Aware Permissions** - Federation and club-scoped access control
- **Advanced Role Management** - Inheritance, switching, and dynamic templates
- **Dynamic User Experience** - Role-based navigation and UI adaptation
- **Scalability** - Support for multi-federation, multi-academy deployments
- **Operational Excellence** - Automated permission inheritance and validation

## Implementation Strategy

### Phase-Based Approach

- **Phase 1**: Context-Aware Permission System (Federation/club scoping)
- **Phase 2**: Advanced Role Features (Inheritance, switching, templates)
- **Phase 3**: Dynamic UI Enhancement (Navigation, component filtering)
- **Phase 4**: Enterprise Integration (Federation support, audit enhancements)
- **Phase 5**: Performance Optimization (Caching, query optimization)

### Key Decisions

#### 1. Context-Aware Permission Architecture

- **Decision**: Implement 6-context permission system (global, federation, club, academy, user, family)
- **Rationale**: Essential for multi-tenant scalability and precise access control
- **Implementation**: Contextual authorization service with metadata support

#### 2. Permission Inheritance Strategy

- **Decision**: Automatic inheritance from lower-level roles with configurable rules
- **Rationale**: Reduces manual assignment errors and maintenance overhead
- **Implementation**: Hierarchical permission evaluation with override capabilities

#### 3. Role-Based Navigation Pattern

- **Decision**: Dynamic navigation filtering based on roles and permissions
- **Rationale**: Better UX and security through appropriate feature exposure
- **Implementation**: Permission-aware navigation components with caching

#### 4. Federation Support Architecture

- **Decision**: Federation-scoped permissions and role management
- **Rationale**: Supports multi-federation deployments and governance
- **Implementation**: Federation-aware authorization with inheritance chains

---

## Phase 1: Context-Aware Permission System

### 1. Enhanced Permission Model

```php
// app/Models/Permission.php (enhanced)
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'action',
        'category',
        'context',
        'context_constraints',
        'metadata',
    ];

    protected $casts = [
        'context_constraints' => 'array',
        'metadata' => 'array',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // Context-aware permission checking
    public function appliesToContext(string $context, array $constraints = []): bool
    {
        if ($this->context === 'global') {
            return true;
        }

        if ($this->context !== $context) {
            return false;
        }

        // Check context constraints
        if (!empty($this->context_constraints)) {
            foreach ($this->context_constraints as $key => $value) {
                if (!isset($constraints[$key]) || $constraints[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    // Permission inheritance logic
    public function getInheritedPermissions(): Collection
    {
        // Implement inheritance from related permissions
        return collect();
    }
}
```

### 2. Contextual Authorization Service

```php
// app/Services/ContextualAuthorizationService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use App\Models\Federation;
use Illuminate\Support\Facades\Cache;

class ContextualAuthorizationService
{
    public function canAccessResource(
        User $user,
        string $permission,
        string $context = 'global',
        array $contextData = []
    ): bool {
        $cacheKey = "user_{$user->id}_permission_{$permission}_context_{$context}";

        return Cache::remember($cacheKey, 300, function () use ($user, $permission, $context, $contextData) {
            return $this->checkPermissionInContext($user, $permission, $context, $contextData);
        });
    }

    protected function checkPermissionInContext(
        User $user,
        string $permission,
        string $context,
        array $contextData
    ): bool {
        // Check global permissions first
        if ($user->hasPermissionTo($permission)) {
            return true;
        }

        // Check context-specific permissions
        switch ($context) {
            case 'club':
                return $this->checkClubPermission($user, $permission, $contextData['club_id'] ?? null);

            case 'federation':
                return $this->checkFederationPermission($user, $permission, $contextData['federation_id'] ?? null);

            case 'academy':
                return $this->checkAcademyPermission($user, $permission, $contextData['academy_id'] ?? null);

            case 'family':
                return $this->checkFamilyPermission($user, $permission, $contextData['family_id'] ?? null);

            default:
                return false;
        }
    }

    protected function checkClubPermission(User $user, string $permission, ?int $clubId): bool
    {
        if (!$clubId) return false;

        $club = Club::find($clubId);
        if (!$club) return false;

        // Club owner has all permissions
        if ($club->user_id === $user->id) {
            return true;
        }

        // Check club-specific roles
        $clubRole = $user->clubMemberships()
            ->where('club_id', $clubId)
            ->wherePivot('role', 'Club Manager')
            ->exists();

        if ($clubRole) {
            // Club managers have management permissions
            return str_contains($permission, 'manage') ||
                   str_contains($permission, 'create') ||
                   str_contains($permission, 'update');
        }

        return false;
    }

    protected function checkFederationPermission(User $user, string $permission, ?int $federationId): bool
    {
        // Federation-level permission checking logic
        return false; // Placeholder
    }

    protected function checkAcademyPermission(User $user, string $permission, ?int $academyId): bool
    {
        // Academy-level permission checking logic
        return false; // Placeholder
    }

    protected function checkFamilyPermission(User $user, string $permission, ?int $familyId): bool
    {
        // Family context permission checking logic
        return false; // Placeholder
    }
}
```

### 3. Database Migration for Context Support

```php
// database/migrations/xxxx_xx_xx_add_context_to_permissions.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->enum('context', ['global', 'federation', 'club', 'academy', 'user', 'family'])
                  ->default('global')
                  ->after('category');
            $table->json('context_constraints')->nullable()->after('context');
        });

        // Update existing permissions to use module.action format
        DB::table('permissions')->update([
            'name' => DB::raw("CONCAT(module, '.', action)"),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['context', 'context_constraints']);
        });
    }
};
```

---

## Phase 2: Advanced Role Features

### 1. Role Inheritance System

```php
// app/Models/Role.php (enhanced)
<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'display_name',
        'level',
        'module',
        'guard_name',
        'inherits_from',
        'is_system_role',
    ];

    protected $casts = [
        'inherits_from' => 'array',
        'is_system_role' => 'boolean',
    ];

    // Get all permissions including inherited ones
    public function getAllPermissions(): Collection
    {
        $permissions = $this->permissions;

        // Add inherited permissions
        if ($this->inherits_from) {
            foreach ($this->inherits_from as $parentRoleName) {
                $parentRole = static::where('name', $parentRoleName)->first();
                if ($parentRole) {
                    $permissions = $permissions->merge($parentRole->permissions);
                }
            }
        }

        return $permissions->unique('id');
    }

    // Check if this role should inherit from another
    public function shouldInheritFrom(Role $otherRole): bool
    {
        return $otherRole->level < $this->level &&
               in_array($otherRole->name, $this->inherits_from ?? []);
    }
}
```

### 2. Role Switching Functionality

```tsx
// resources/js/components/RoleSwitcher.tsx
import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';

export default function RoleSwitcher() {
    const { auth } = usePage().props;
    const user = auth.user;
    const currentRole = auth.current_role;

    const availableRoles = user.roles || [];
    const [selectedRole, setSelectedRole] = useState(currentRole?.name || '');

    const handleRoleSwitch = () => {
        router.post(
            '/user/switch-role',
            {
                role: selectedRole,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    window.location.reload(); // Refresh to update permissions
                },
            },
        );
    };

    if (availableRoles.length <= 1) {
        return null; // Don't show if user only has one role
    }

    return (
        <div className="flex items-center gap-2 rounded-lg bg-muted p-2">
            <span className="text-sm font-medium">Active Role:</span>
            <Badge variant="secondary">{currentRole?.display_name}</Badge>

            <Select value={selectedRole} onValueChange={setSelectedRole}>
                <SelectTrigger className="w-48">
                    <SelectValue placeholder="Switch role..." />
                </SelectTrigger>
                <SelectContent>
                    {availableRoles.map((role: any) => (
                        <SelectItem key={role.name} value={role.name}>
                            {role.display_name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Button
                size="sm"
                onClick={handleRoleSwitch}
                disabled={selectedRole === currentRole?.name}
            >
                Switch
            </Button>
        </div>
    );
}
```

### 3. Enhanced Role Templates

```php
// app/Models/RoleTemplate.php (enhanced)
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoleTemplate extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'category',
        'description',
        'roles',
        'permissions',
        'context',
        'inheritance_rules',
        'is_system_template',
        'is_active',
    ];

    protected $casts = [
        'roles' => 'array',
        'permissions' => 'array',
        'inheritance_rules' => 'array',
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system_template', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Apply template to user
    public function applyToUser(User $user, array $contextData = []): void
    {
        // Apply roles
        if ($this->roles) {
            $user->syncRoles($this->roles);
        }

        // Apply context-specific permissions
        if ($this->permissions && $this->context) {
            // Context-aware permission assignment logic
        }

        // Handle inheritance rules
        if ($this->inheritance_rules) {
            // Apply inheritance configuration
        }
    }

    // Validate template compatibility
    public function validateForContext(string $context, array $contextData = []): array
    {
        $errors = [];

        // Template validation logic
        if ($this->context && $this->context !== $context) {
            $errors[] = "Template is not compatible with {$context} context";
        }

        return $errors;
    }
}
```

---

## Phase 3: Dynamic UI Enhancement

### 1. Role-Based Navigation Component

```tsx
// resources/js/components/RoleBasedNav.tsx
import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { usePermissions } from '@/hooks/usePermissions';

interface NavItem {
    name: string;
    href: string;
    icon?: string;
    permission?: string;
    roles?: string[];
    context?: string;
    children?: NavItem[];
}

interface RoleBasedNavProps {
    items: NavItem[];
    context?: string;
    contextData?: any;
}

export default function RoleBasedNav({
    items,
    context,
    contextData,
}: RoleBasedNavProps) {
    const { checkPermission, hasRole } = usePermissions();
    const { auth } = usePage().props;

    const canAccessItem = (item: NavItem): boolean => {
        // Check permission
        if (
            item.permission &&
            !checkPermission(item.permission, context, contextData)
        ) {
            return false;
        }

        // Check roles
        if (item.roles && !item.roles.some((role) => hasRole(role))) {
            return false;
        }

        // Check context
        if (item.context && item.context !== context) {
            return false;
        }

        return true;
    };

    const renderNavItem = (item: NavItem) => {
        if (!canAccessItem(item)) {
            return null;
        }

        if (item.children) {
            return (
                <div key={item.name} className="nav-group">
                    <div className="nav-group-title">{item.name}</div>
                    {item.children.map(renderNavItem)}
                </div>
            );
        }

        return (
            <Link key={item.name} href={item.href} className="nav-item">
                {item.icon && <span className="nav-icon">{item.icon}</span>}
                {item.name}
            </Link>
        );
    };

    return <nav className="role-based-nav">{items.map(renderNavItem)}</nav>;
}
```

### 2. Permission-Based Component Filtering

```tsx
// resources/js/hooks/usePermissions.ts
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage().props;

    const checkPermission = (
        permission: string,
        context?: string,
        contextData?: any,
    ): boolean => {
        // Client-side permission checking for UI optimization
        // Full server-side validation still required

        // Check global permissions first
        if (auth.permissions?.includes(permission)) {
            return true;
        }

        // Context-aware checking (simplified client-side version)
        if (context === 'club' && contextData?.club_id) {
            // Check if user has club-specific permissions
            return (
                auth.club_permissions?.[contextData.club_id]?.includes(
                    permission,
                ) ?? false
            );
        }

        return false;
    };

    const hasRole = (role: string): boolean => {
        return auth.roles?.includes(role) ?? false;
    };

    const hasAnyRole = (roles: string[]): boolean => {
        return roles.some((role) => hasRole(role));
    };

    const switchRole = (roleName: string): void => {
        router.post(
            '/user/switch-role',
            { role: roleName },
            {
                preserveScroll: true,
                onSuccess: () => window.location.reload(),
            },
        );
    };

    return {
        checkPermission,
        hasRole,
        hasAnyRole,
        switchRole,
    };
}
```

### 3. Dynamic Feature Toggles

```tsx
// resources/js/components/FeatureToggle.tsx
import React from 'react';
import { usePermissions } from '@/hooks/usePermissions';

interface FeatureToggleProps {
    feature: string;
    fallback?: React.ReactNode;
    context?: string;
    contextData?: any;
    children: React.ReactNode;
}

export default function FeatureToggle({
    feature,
    fallback = null,
    context,
    contextData,
    children,
}: FeatureToggleProps) {
    const { checkPermission } = usePermissions();

    if (!checkPermission(feature, context, contextData)) {
        return <>{fallback}</>;
    }

    return <>{children}</>;
}
```

---

## Phase 4: Enterprise Integration

### 1. Federation Support

```php
// app/Models/Federation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Federation extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'governing_body',
        'is_active',
    ];

    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    public function academies(): HasMany
    {
        return $this->hasMany(Academy::class);
    }

    // Federation-specific permissions
    public function getFederationPermissions(): Collection
    {
        return Permission::where('context', 'federation')
            ->whereJsonContains('context_constraints->federation_id', $this->id)
            ->get();
    }
}
```

### 2. Enhanced Audit System

```php
// app/Services/AuditService.php
<?php

namespace App\Services;

use App\Models\RoleAssignmentAudit;
use App\Models\User;

class AuditService
{
    public function logRoleAssignment(
        User $assignedBy,
        User $targetUser,
        string $roleName,
        string $action,
        array $metadata = []
    ): void {
        RoleAssignmentAudit::create([
            'assigned_by' => $assignedBy->id,
            'target_user_id' => $targetUser->id,
            'role_name' => $roleName,
            'action' => $action,
            'metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
            ]),
        ]);
    }

    public function logPermissionCheck(
        User $user,
        string $permission,
        string $context,
        bool $granted,
        array $contextData = []
    ): void {
        // Enhanced permission checking audit
        // Implementation for detailed permission audit trails
    }
}
```

---

## Phase 5: Performance Optimization

### 1. Permission Caching Enhancement

```php
// app/Services/PermissionCacheService.php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PermissionCacheService
{
    public function getUserPermissions(User $user, string $context = 'global', array $contextData = []): array
    {
        $cacheKey = $this->generateCacheKey($user, $context, $contextData);

        return Cache::store('redis')->remember(
            $cacheKey,
            3600, // 1 hour
            fn() => $this->calculateUserPermissions($user, $context, $contextData)
        );
    }

    protected function generateCacheKey(User $user, string $context, array $contextData): string
    {
        $contextHash = md5(json_encode($contextData));
        return "user_permissions:{$user->id}:{$context}:{$contextHash}";
    }

    protected function calculateUserPermissions(User $user, string $context, array $contextData): array
    {
        // Complex permission calculation logic
        // Include role inheritance, context constraints, etc.
        return [];
    }

    public function invalidateUserPermissions(User $user): void
    {
        // Invalidate all permission caches for user
        $pattern = "user_permissions:{$user->id}:*";
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            Redis::del($key);
        }
    }

    public function invalidateContextPermissions(string $context, int $contextId): void
    {
        // Invalidate permissions for specific context
        $pattern = "user_permissions:*:$context:*";
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            if (str_contains($key, $contextId)) {
                Redis::del($key);
            }
        }
    }
}
```

### 2. Query Optimization

```php
// app/Services/OptimizedRoleService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class OptimizedRoleService
{
    public function getUsersWithRolesAndPermissions(Collection $users): Collection
    {
        return $users->load([
            'roles:id,name,display_name,level',
            'roles.permissions:id,name,module,action,context'
        ]);
    }

    public function getRolesWithInheritance(Collection $roles): Collection
    {
        return $roles->load([
            'permissions',
            'inheritedRoles.permissions'
        ]);
    }

    public function bulkCheckPermissions(array $userIds, array $permissions, string $context = 'global'): array
    {
        // Optimized bulk permission checking
        // Single query approach for multiple users/permissions
        return [];
    }
}
```

---

## Success Criteria

### **Context-Aware Authorization**

- [ ] Permission contexts (global, federation, club, academy, user, family) implemented
- [ ] Contextual authorization service working with metadata support
- [ ] Context constraints validated in permission checks

### **Advanced Role Management**

- [ ] Role inheritance system functional with configurable rules
- [ ] Role switching UI implemented and working
- [ ] Enhanced role templates with categories and inheritance

### **Dynamic User Experience**

- [ ] Role-based navigation filtering implemented
- [ ] Permission-aware component rendering working
- [ ] Feature toggles based on roles and context

### **Enterprise Integration**

- [ ] Federation support with scoped permissions
- [ ] Enhanced audit trails with metadata
- [ ] Permission caching with Redis optimization

### **Performance & Scalability**

- [ ] Permission cache invalidation working
- [ ] Optimized database queries for bulk operations
- [ ] Context-aware caching implemented

---

## Implementation Order (Week-by-Week Breakdown)

### **Week 1: Foundation Setup**

- Add context fields to permissions table
- Implement ContextualAuthorizationService
- Create PermissionCacheService

### **Week 2: Role Inheritance System**

- Enhance Role model with inheritance
- Implement role switching functionality
- Update role templates with categories

### **Week 3: Dynamic UI Components**

- Implement RoleBasedNav component
- Create permission hooks and utilities
- Add feature toggle components

### **Week 4: Enterprise Integration**

- Add federation support
- Implement enhanced audit system
- Integrate context-aware caching

### **Week 5: Performance & Testing**

- Optimize queries and caching
- Comprehensive testing of context features
- Performance benchmarking and tuning

---

## Files to Create/Modify

### New Models

- Enhanced `app/Models/Permission.php` (context support)
- `app/Models/Federation.php` (federation support)
- `app/Models/RoleAssignmentAudit.php` (enhanced audit)

### New Services

- `app/Services/ContextualAuthorizationService.php`
- `app/Services/PermissionCacheService.php`
- `app/Services/AuditService.php`
- `app/Services/OptimizedRoleService.php`

### Enhanced Controllers

- `app/Http/Controllers/Auth/RoleSwitchController.php`
- Enhanced `app/Http/Controllers/Admin/RoleAssignmentController.php`

### New Middleware

- `app/Http/Middleware/ContextAwareAuthorization.php`

### React Components

- `resources/js/components/RoleSwitcher.tsx`
- `resources/js/components/RoleBasedNav.tsx`
- `resources/js/components/FeatureToggle.tsx`
- `resources/js/hooks/usePermissions.ts`

### Database Migrations

- `database/migrations/xxxx_xx_xx_add_context_to_permissions.php`
- `database/migrations/xxxx_xx_xx_add_federation_support.php`
- `database/migrations/xxxx_xx_xx_add_role_inheritance.php`

---

## Key Dependencies

- **Sprint 5 Completion**: Basic RBAC system must be working
- **Redis Setup**: For advanced permission caching
- **Federation Data Model**: If federation support is included
- **UI Component Library**: For enhanced React components

---

**Sprint Priority**: HIGH
**Estimated Effort**: 5 weeks (25 days)
**Dependencies**: Sprint 5 completion
**Business Value**: Enterprise scalability, advanced UX, comprehensive audit trails
**Technology Stack**: Laravel 12 + React 19 + Inertia.js v2 + Redis
