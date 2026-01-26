# Sprint 5: RBAC Refinement & Technical Polish

## 🎯 Sprint Goal
**Refine permissions, complete technical cleanup, TypeScript migration, 100% code quality. Keep intelligent union nav (no selector must - optional future).**

## 📋 Sprint Overview
Sprint 5 polishes Sprint 4 RBAC: permissions seeding, sidebar cleanup, TS multi-file, lint/types fix. Union nav working - selector future optional.

**Success Signal**: _"RBAC production-ready: permissions seeded, clean components, TS organized, 100% lint/types/tests pass, debt cleared."_

---
## 🔍 Post-Sprint 4 Deep Dive
From Sprint 4 + debt:

| Issue | Sprint 4 | Sprint 5 |
|-------|----------|----------|
| Permissions | Basic gates | ✅ Full seeding |
| Components | Dups | ✅ Cleanup |
| TS | Single file | ✅ Multi-file |
| Quality | 95% | ✅ 100% |

**Missing - Now Added:**
- ✅ Permissions seeder
- ✅ Component cleanup
- ✅ TS migration
- ✅ Lint/types fix

**Union nav kept** - intelligent multi-role nav working.

---
## 🔍 Building on Sprint 4
Sprint 4 enterprise RBAC. Sprint 5 polish:

| Category | Sprint 4 | Sprint 5 |
|----------|----------|----------|
| Permissions | Gates | ✅ Seeding |
| Code | Functional | ✅ Clean |
| TS | Single | ✅ Multi |
| Quality | 95% | ✅ 100% |

---
## 📊 Sprint Scope & Effort
**Time Estimate**: 4 days (3 hours/day)

**Complexity**: Low (cleanup)

### What We Complete
1. Permissions seeding
2. Sidebar cleanup
3. TS migration
4. Lint/types polish
5. Validation

### Dependencies
- ✅ Sprint 4 complete

---
## 🏗️ Implementation Plan

### **Day 1: Permissions Seeding (2 hours)**
#### **1.1 PermissionSeeder**
**File**: `database/seeders/PermissionSeeder.php` (new)

**Granular**:
```php
Permission::create(['name' => 'view.users']);
Role::findByName('Super Admin')->givePermissionTo(Permission::all());
Role::findByName('Coach')->givePermissionTo(['view.athletes', 'manage.programs']);
```

**Run**: `php artisan db:seed --class=PermissionSeeder`

### **Day 2: Cleanup (1 hour)**
#### **2.1 Sidebar Cleanup**
**Delete**: `resources/js/components/navigation-sidebar.tsx`
**Comment**: app-sidebar.tsx `// Merged - Sprint 5 cleanup complete`

### **Day 3: TS Migration (2 hours)**
#### **3.1 Multi-file TS**
**Files**: `resources/js/types/{auth.ts, nav.ts, shared.ts, index.ts}`

**auth.ts**:
```typescript
export interface User {
    id: number;
    first_name: string;
    // ...
}
```

**index.ts** re-export.

### **Day 4: Quality/Validation (1 hour)**
#### **4.1 Lint/Types**
```bash
npm run lint --fix
npm run types
npm run format
```

#### **4.2 Tests**
`php artisan test --coverage`

---
## 📁 Files
### New (1)
1. PermissionSeeder.php

### Modified (4)
1. app-sidebar.tsx (comment)
2. types/auth.ts
3. types/nav.ts
4. types/shared.ts
5. types/index.ts

---
## 🚀 Readiness Checklist
- [ ] Permissions seeded
- [ ] navigation-sidebar deleted
- [ ] TS multi-file
- [ ] Lint/types 100%
- [ ] Tests 117/117

**Production Ready.** 🚀
