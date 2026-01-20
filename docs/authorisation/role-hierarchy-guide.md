# Role Hierarchy Guide

## Overview

The Octomat platform uses a role-based access control (RBAC) system with a clear hierarchical structure. This guide explains how roles are organized, their authority levels, and the rules governing role assignments.

## Role Hierarchy System

### Authority Levels (100-1000 Scale)

Roles are assigned numeric authority levels from 100 (lowest) to 1000 (highest). Higher numbers indicate greater authority and access permissions.

| Level | Role              | Description                          | Can Assign Roles ≤ Level |
| ----- | ----------------- | ------------------------------------ | ------------------------ |
| 1000  | Super Admin       | Complete system access               | All roles                |
| 900   | Federation Admin  | Federation-wide administration       | 800 and below            |
| 800   | Event Organiser   | Event management and coordination    | 700 and below            |
| 750   | Affiliate Manager | Academy and federation relationships | 600 and below            |
| 700   | Academy Owner     | Full academy management              | 600 and below            |
| 600   | Club Manager      | Club leadership and management       | 500 and below            |
| 500   | Club Admin        | Club administration support          | 400 and below            |
| 400   | Coach             | Training and athlete management      | 300 and below            |
| 300   | Event Staff       | Event operations support             | 200 and below            |
| 250   | Parent/Guardian   | Family account management            | 100 and below            |
| 200   | Athlete           | Competition participation            | None                     |
| 100   | General User      | Basic platform access                | None                     |

### Visual Hierarchy

```
Super Admin (1000)     ← Complete system control
├── Federation Admin (900)     ← Multi-academy oversight
├── Event Organiser (800)      ← Event coordination
├── Affiliate Manager (750)    ← Partnership management
├── Academy Owner (700)        ← Academy governance
├── Club Manager (600)         ← Club leadership
├── Club Admin (500)           ← Club administration
├── Coach (400)                ← Training leadership
├── Event Staff (300)          ← Event support
├── Parent/Guardian (250)      ← Family management
├── Athlete (200)              ← Participant
└── General User (100)         ← Basic access
```

## Role Assignment Rules

### 1. Authority Principle

Users can only assign roles at or below their own authority level:

- **Super Admin** (1000) can assign any role
- **Club Manager** (600) can assign roles ≤ 600 (Coach, Event Staff, etc.)
- **Coach** (400) can assign roles ≤ 400 (Event Staff, Parent/Guardian, etc.)

**Exception:** Super Admins bypass all hierarchy restrictions.

### 2. Self-Assignment Restrictions

Users cannot assign high-level administrative roles to themselves:

- **Blocked:** Self-assignment of Federation Admin (900) or Super Admin (1000)
- **Allowed:** Self-demotion to lower roles
- **Purpose:** Prevents privilege escalation and ensures oversight

### 3. Role Conflict Prevention

Users cannot hold multiple roles within 100 authority points of each other:

- **Example:** Cannot have both Coach (400) and Club Admin (500) simultaneously
- **Reason:** Prevents authority confusion and overlapping responsibilities
- **Resolution:** Remove conflicting role before assigning new one

## Permission Matrix

### System Permissions

| Permission     | Super Admin | Federation Admin | Academy Owner | Club Manager | Coach |
| -------------- | ----------- | ---------------- | ------------- | ------------ | ----- |
| `system.admin` | ✅          | ❌               | ❌            | ❌           | ❌    |
| `system.audit` | ✅          | ✅               | ❌            | ❌           | ❌    |
| `users.manage` | ✅          | ✅               | ❌            | ❌           | ❌    |
| `roles.manage` | ✅          | ❌               | ❌            | ❌           | ❌    |

### Content Permissions

| Permission                  | Super Admin | Federation Admin | Academy Owner | Club Manager | Coach |
| --------------------------- | ----------- | ---------------- | ------------- | ------------ | ----- |
| `federation.admin`          | ✅          | ✅               | ❌            | ❌           | ❌    |
| `federation.members.manage` | ✅          | ✅               | ✅            | ❌           | ❌    |
| `academies.create`          | ✅          | ✅               | ✅            | ❌           | ❌    |
| `academies.view`            | ✅          | ✅               | ✅            | ✅           | ❌    |
| `academies.manage`          | ✅          | ✅               | ✅            | ❌           | ❌    |
| `clubs.manage`              | ✅          | ✅               | ✅            | ✅           | ❌    |
| `club.members.manage`       | ✅          | ✅               | ✅            | ✅           | ❌    |
| `events.create`             | ✅          | ✅               | ✅            | ✅           | ❌    |
| `events.view`               | ✅          | ✅               | ✅            | ✅           | ✅    |
| `events.manage`             | ✅          | ✅               | ✅            | ✅           | ❌    |

### User Permissions

| Permission             | All Roles |
| ---------------------- | --------- |
| `profile.basic.manage` | ✅        |

## Common Role Assignment Scenarios

### Scenario 1: New Academy Setup

```
Federation Admin assigns:
├── Academy Owner → Full academy control
├── Club Manager → Club leadership
└── Coaches → Training staff
```

### Scenario 2: Event Management

```
Event Organiser assigns:
├── Event Staff → Support roles
└── Volunteer Coaches → Event-specific coaching
```

### Scenario 3: Club Administration

```
Club Manager assigns:
├── Club Admin → Administrative support
├── Coaches → Training staff
└── Event Staff → Club event support
```

## Security Considerations

### Privilege Escalation Prevention

- Hierarchical validation prevents unauthorized role assignments
- Self-assignment restrictions maintain administrative oversight
- Role conflicts prevent authority confusion

### Audit Trail

All role assignments are logged for security auditing and compliance.

### Emergency Access

Super Admins have unrestricted access for system maintenance and emergency situations.

## Development Guidelines

### Frontend Role Checking

```typescript
const hasRole = (role: string) => auth.roles?.includes(role);

// Check specific permissions
if (hasRole('Super Admin') || hasRole('Academy Owner')) {
    // Show admin controls
}
```

### Backend Authorization

```php
// Check role permissions
if ($user->hasRole('Academy Owner')) {
    // Allow academy management
}

// Check hierarchical authority
if ($user->getHighestRoleLevel() >= 600) {
    // Allow club management
}
```

## Maintenance

### Adding New Roles

1. Choose appropriate authority level (multiples of 50 recommended)
2. Define permissions in RoleSeeder
3. Update this documentation
4. Add frontend role checks where needed

### Modifying Authority Levels

1. Update role level in database seeder
2. Test all role assignment validations
3. Update documentation
4. Communicate changes to administrators

## Support

For questions about role hierarchy or permissions:

- Check existing role definitions in `database/seeders/RoleSeeder.php`
- Review test cases in `tests/Feature/RoleTest.php`
- Consult the technical debt report for known limitations

---

**Last Updated:** Sprint 2 Extension
**Version:** 1.0
**Next Review:** Sprint 5 (Advanced RBAC)
