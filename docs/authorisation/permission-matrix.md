# Permission Matrix

## Overview

This document provides a comprehensive mapping of roles to permissions in the Octomat platform. Each permission controls access to specific functionality and is granted based on the user's role hierarchy.

## Permission Categories

### System Administration Permissions

| Permission     | Description                                | Granted To                    |
| -------------- | ------------------------------------------ | ----------------------------- |
| `system.admin` | Complete system administration access      | Super Admin                   |
| `system.audit` | Access to system audit logs and monitoring | Super Admin, Federation Admin |
| `users.manage` | Create, edit, delete user accounts         | Super Admin, Federation Admin |
| `roles.manage` | Assign and modify user roles               | Super Admin                   |

### Federation & Organization Permissions

| Permission                  | Description                                      | Granted To                                                 |
| --------------------------- | ------------------------------------------------ | ---------------------------------------------------------- |
| `federation.admin`          | Federation-wide administrative control           | Super Admin, Federation Admin                              |
| `federation.members.manage` | Manage federation memberships and affiliations   | Super Admin, Federation Admin, Academy Owner               |
| `academies.create`          | Create new academies                             | Super Admin, Federation Admin, Academy Owner               |
| `academies.view`            | View academy information                         | Super Admin, Federation Admin, Academy Owner, Club Manager |
| `academies.manage`          | Full academy management (edit, delete, settings) | Super Admin, Federation Admin, Academy Owner               |

### Club Management Permissions

| Permission            | Description                                     | Granted To                                                 |
| --------------------- | ----------------------------------------------- | ---------------------------------------------------------- |
| `clubs.manage`        | Create and manage clubs within academies        | Super Admin, Federation Admin, Academy Owner, Club Manager |
| `club.members.manage` | Manage club memberships and athlete assignments | Super Admin, Federation Admin, Academy Owner, Club Manager |

### Event Management Permissions

| Permission      | Description                                   | Granted To                                                        |
| --------------- | --------------------------------------------- | ----------------------------------------------------------------- |
| `events.create` | Create new events and competitions            | Super Admin, Federation Admin, Academy Owner, Club Manager        |
| `events.view`   | View event details and results                | Super Admin, Federation Admin, Academy Owner, Club Manager, Coach |
| `events.manage` | Full event management (edit, cancel, results) | Super Admin, Federation Admin, Academy Owner, Club Manager        |

### User Profile Permissions

| Permission             | Description                      | Granted To              |
| ---------------------- | -------------------------------- | ----------------------- |
| `profile.basic.manage` | Manage basic profile information | All authenticated users |

## Role-Based Permission Summary

### Super Admin (Level 1000)

**System Control:** Complete access to all platform features

- All system administration permissions
- All federation and organization permissions
- All club and event permissions
- Can assign any role to any user

### Federation Admin (Level 900)

**Federation Oversight:** Multi-academy governance and compliance

- System audit access
- User management within federation
- All academy and club permissions
- Can assign roles up to Event Organiser level

### Event Organiser (Level 800)

**Event Coordination:** Tournament and competition management

- Event creation and management
- Can assign roles up to Affiliate Manager level

### Affiliate Manager (Level 750)

**Partnership Management:** Academy relationships and growth

- Academy viewing and federation member management
- Can assign roles up to Academy Owner level

### Academy Owner (Level 700)

**Academy Governance:** Complete academy management

- Academy creation and full management
- Club and event management within academy
- Can assign roles up to Club Manager level

### Club Manager (Level 600)

**Club Leadership:** Club operations and athlete development

- Club management and member administration
- Event management at club level
- Can assign roles up to Club Admin level

### Club Admin (Level 500)

**Club Administration:** Administrative support for club operations

- Club member management
- Can assign roles up to Coach level

### Coach (Level 400)

**Training Leadership:** Athlete development and competition preparation

- Event viewing and participation
- Can assign roles up to Event Staff level

### Event Staff (Level 300)

**Event Support:** Competition and event operations

- Event participation and support roles
- Can assign basic participant roles

### Parent/Guardian (Level 250)

**Family Management:** Child athlete account management

- Basic profile management
- Can manage associated athlete accounts

### Athlete (Level 200)

**Participant:** Competition and training participation

- Event participation
- Basic profile management

### General User (Level 100)

**Basic Access:** Platform participation

- Basic profile management
- Limited viewing access

## Permission Implementation

### Backend Checks

```php
// Check specific permission
if ($user->hasPermissionTo('academies.create')) {
    // Allow academy creation
}

// Check role-based access
if ($user->hasRole('Academy Owner')) {
    // Allow academy management
}
```

### Frontend Guards

```typescript
// Check permissions in React components
const canManageAcademies = auth.permissions?.includes('academies.manage');

{canManageAcademies && (
    <AcademyManagementPanel />
)}
```

### Middleware Protection

```php
// Route protection
Route::middleware(['auth', 'permission:academies.create'])->group(function () {
    Route::post('/academies', [AcademyController::class, 'store']);
});
```

## Permission Assignment Rules

### Automatic Assignment

Permissions are automatically assigned when roles are created via the `RoleSeeder`. Each role receives a predefined set of permissions based on its authority level and responsibilities.

### Manual Permission Management

Super Admins can manually adjust individual user permissions through the Spatie Laravel Permission interface, though this should be used sparingly to maintain system consistency.

### Permission Inheritance

- Higher-level roles include all permissions of lower-level roles within their domain
- System-level permissions are restricted to administrative roles only
- Domain-specific permissions follow organizational hierarchy

## Security Considerations

### Least Privilege Principle

Users receive only the minimum permissions required for their role, preventing unauthorized access to sensitive functionality.

### Permission Auditing

All permission changes are logged through Laravel's activity logging for security auditing and compliance.

### Emergency Access

Super Admins maintain unrestricted access for system maintenance and emergency situations.

## Maintenance Guidelines

### Adding New Permissions

1. Define permission name using `module.action` convention
2. Add to `RoleSeeder` with appropriate role assignments
3. Update this permission matrix
4. Implement backend authorization checks
5. Add frontend permission guards if needed

### Modifying Role Permissions

1. Update `RoleSeeder` with new permission assignments
2. Test affected functionality
3. Update documentation
4. Communicate changes to affected users

### Permission Cleanup

Regularly review and remove unused permissions to maintain system clarity.

## Testing Permissions

### Unit Tests

```php
test('academy owner can create academies', function () {
    $user = User::factory()->create();
    $user->assignRole('Academy Owner');

    expect($user->hasPermissionTo('academies.create'))->toBeTrue();
});
```

### Integration Tests

```php
test('unauthorized users cannot access admin routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/admin/academies')
        ->assertForbidden();
});
```

## Support

For permission-related issues:

- Check role definitions in `database/seeders/RoleSeeder.php`
- Review test cases in `tests/Feature/RoleTest.php`
- Consult the role hierarchy guide for authority levels

---

**Last Updated:** Sprint 2 Extension
**Version:** 1.0
**Next Review:** Sprint 5 (Advanced RBAC)
