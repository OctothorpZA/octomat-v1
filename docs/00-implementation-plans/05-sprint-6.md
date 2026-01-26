# Sprint 5: Advanced User Management & Performance Infrastructure

## Overview

Build comprehensive user management system with enterprise-grade search, bulk operations, role-based profile validation, and performance monitoring infrastructure. This completes the user/RBAC foundation before introducing multi-tenancy (Federations/Academies/Clubs).

## Business Context

- **Enterprise User Management**: Admins need search, bulk ops, validation for 10k+ users.
- **Compliance**: Profile completeness for roles (Athlete DOB, Coach certs).
- **Performance**: Dashboard metrics, N+1 fixes, load testing for scale.
- **Foundation**: Prepares for Sprint 6 multi-tenancy.

## Implementation Scope

Focus on admin tools + perf. No multi-tenancy yet.

## Database Changes

### New Indexes & Migration

```php
// database/migrations/add_user_search_indexes.php
Schema::table('users', function (Blueprint $table) {
    $table->index(['first_name', 'last_name']);
    $table->index('date_of_birth');
    $table->fullText(['first_name', 'middle_names', 'last_name', 'email']);
});
```

## Application Changes Required

### 1. UserSearchController

```php
// app/Http/Controllers/Admin/UserSearchController.php
public function index(Request $request) {
    $query = User::query()
        ->with('roles')
        ->when($request->search, fn ($q, $search) => $q
            ->whereFullText(['first_name', 'middle_names', 'last_name', 'email'], $search)
        )
        ->when($request->role, fn ($q, $role) => $q->role($role));

    return Inertia::render('admin/users', [
        'users' => $query->paginate(20)->withQueryString(),
        'filters' => $request->only(['search', 'role']),
    ]);
}
```

### 2. User Model Enhancements

```php
// app/Models/User.php
public function getIsProfileCompleteAttribute(): bool {
    return match($this->primary_role) {
        'athlete' => filled($this->first_name, $this->last_name, $this->date_of_birth),
        'coach' => filled($this->first_name, $this->last_name, $this->certifications),
        default => true,
    };
}
```

### 3. BulkOps FormRequest

```php
// app/Http/Requests/Admin/BulkUserRequest.php
public function rules(): array {
    return [
        'users' => 'array',
        'users.*' => 'exists:users,id',
        'action' => 'in:assign_role,remove_role,activate,deactivate',
        'role' => 'string|exists:roles,name',
    ];
}
```

## Frontend Changes

### Admin Users Page

```tsx
// resources/js/pages/admin/users.tsx
// shadcn table with checkbox selection, filters, bulk modal
const BulkActions = ({ selectedUsers }) => (
    <DropdownMenu>
        <DropdownMenuTrigger>
            Actions ({selectedUsers.length})
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuItem onClick={() => bulkAssignRole()}>
                Assign Role
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
);
```

## Testing Strategy

### Feature Tests

```php
// tests/Feature/UserSearchTest.php
test('searches users by full-text', function () {
    $user = User::factory()->create(['first_name' => 'John']);
    $response = $this->actingAs(admin())->get('/admin/users?search=John');
    $response->assertInertia(fn ($page) => $page->has('users.data.0.id', $user->id));
});
```

## Implementation Order (Phase-Based)

### Phase 1: Search/Filter (Day 1)

1. Migration/indexes.
2. UserSearchController.
3. users.tsx table/filters.

### Phase 2: Bulk Ops (Day 2)

1. UserManagementController.
2. BulkUserRequest.
3. Bulk modal UI.

### Phase 3: Validation (Day 3)

1. User::isProfileComplete().
2. Gates/policies.
3. Dashboard widget.

### Phase 4: Perf Infra (Day 4)

1. Telescope config.
2. DashboardStatsService enhancements.
3. Indexes/N+1 fixes.

### Phase 5: Admin UI (Day 5)

1. users/{id}/edit.
2. Impersonation enhancements.

### Phase 6: Testing (Day 6)

20+ Pest/Vitest/load tests.

## Key Decisions/Tradeoffs

| Feature | Decision    | Rationale    | Alt    |
| ------- | ----------- | ------------ | ------ |
| Search  | PG tsvector | Native, fast | Scout  |
| Bulk    | Sync batch  | UX           | Queued |
| Perf    | Telescope   | Std          | Custom |

## Files (25 est.)

**New (15):** UserSearchController.php, UserManagementController.php, BulkUserRequest.php, migrations/search_indexes.php, users.tsx, UserFilters.tsx, etc.
**Mod (10):** User.php, DashboardController.php, RoleTest.php, dashboard.tsx.

## Success Metrics

- 140+ tests.
- Search <200ms/10k.
- Bulk 500 users.
- No N+1 queries.

Approve or tweak?
