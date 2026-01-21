# Sprint 3: RBAC UI Foundation (Foundation Sprint)

## 🎯 Sprint Goal

**Users can log in and land on role-appropriate dashboards. Super_admin gets protected admin tools. Role-based access control proven in UI.**

## 📋 Sprint Overview

This is the **foundation sprint** that establishes core RBAC infrastructure. We build the essential role-gated UI with **modern React/Inertia.js patterns** and **enterprise-grade testing**, creating a solid base for Sprint 4's complex enhancements.

**Success Signal**: _"I can log in as admin and get redirected to admin dashboard with admin tools visible. I can log in as normal user and see user dashboard with appropriate content. Navigation adapts to my role. Non-admins can't access admin pages. All interactions have proper loading states and accessibility."_

---

## 🔍 **Analysis of Prototype Plans & Missing Features**

After deep analysis of all 6 archived sprint files (3 role-aware UI versions + 3 access control versions), this Sprint 3 incorporates the **best practices from all versions** while translating from Livewire to React/Inertia.js:

### **📊 Archive Feature Analysis**

| Archive Version          | Key Missing Features                                | Status          |
| ------------------------ | --------------------------------------------------- | --------------- |
| **v01**                  | Basic widgets, navigation, multi-role support       | ✅ Incorporated |
| **v05**                  | Enhanced admin UI, search functionality, logging    | ⚠️ **MISSING**  |
| **v06**                  | Livewire v4 patterns, loading states, accessibility | ⚠️ **MISSING**  |
| **Access Control v1-v3** | Advanced admin interface, event dispatching         | ⚠️ **MISSING**  |

**Critical Missing Elements Added to Sprint 3:**

- ✅ Modern React/Inertia.js patterns (loading states, debouncing, accessibility)
- ✅ Enhanced admin interface with search functionality
- ✅ Real database statistics (not placeholders)
- ✅ Event dispatching for notifications
- ✅ Comprehensive CSS transitions and animations
- ✅ Expanded test coverage (6+ test files)
- ✅ Accessibility features (ARIA, keyboard navigation)

---

## 📊 Sprint Scope & Effort

### **Time Estimate**: 4 days (3-4 hours/day)

### **Complexity**: Moderate (role-aware logic, React/Inertia.js translation)

### **What We Build (Foundation Only)**

- ✅ **Two Simple Dashboards**: User dashboard vs Admin dashboard (no widgets yet)
- ✅ **Role-Aware Redirects**: Automatic super_admin → admin dashboard routing
- ✅ **Hard-Coded Navigation**: Role-gated menu items without dynamic builders
- ✅ **Table-Based Admin UI**: Users table with dropdown assign/remove actions
- ✅ **Pagination Support**: Essential for admin scalability
- ✅ **Access Control Testing**: 6+ tests covering all authorization scenarios

### **What We Skip (Sprint 4)**

Based on prototype evolution, these complex features are **intentionally postponed** to Sprint 4:

- ❌ Search functionality in role management
- ❌ Audit logging for role changes
- ❌ Real-time notifications/events
- ❌ Custom middleware for redirects
- ❌ Advanced navigation with JavaScript components
- ❌ Toast notification systems
- ❌ Real database statistics/widgets

---

## 🏗️ Implementation Architecture

### **React/Inertia.js Translation Strategy**

The original Livewire plans are translated to React/Inertia.js:

| Livewire (Original)      | React/Inertia.js (New)         |
| ------------------------ | ------------------------------ |
| `@livewire('dashboard')` | `Inertia::render('dashboard')` |
| `<flux:card>` components | `<Card>` shadcn/ui components  |
| Livewire properties      | Inertia props from controllers |
| Route middleware         | Controller-based redirects     |
| Blade conditionals       | React conditional rendering    |

### **Core Architecture Patterns**

```php
// Controller Pattern - Role-aware data provision
class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (auth()->user()->hasRole('super_admin')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('dashboard', [
            'user' => auth()->user(),
            // No complex widgets yet - foundation only
        ]);
    }
}
```

### **Modern React/Inertia.js Conventions (From Archive v06)**

**Loading States**:

```tsx
// Modern pattern with proper state management
<Button onClick={handleAction} disabled={isLoading}>
    <span className={isLoading ? 'opacity-0' : 'opacity-100'}>
        {isLoading ? 'Processing...' : 'Save Changes'}
    </span>
</Button>
```

**Debounced Search**:

```tsx
const [search, setSearch] = useState('');
const debouncedSearch = useDebounce(search, 300);

useEffect(() => {
    router.get(
        '/admin/roles',
        { search: debouncedSearch },
        {
            preserveState: true,
            replace: true,
        },
    );
}, [debouncedSearch]);
```

**Accessibility Features**:

```tsx
<DropdownMenu>
    <DropdownMenuTrigger
        aria-expanded={isOpen}
        aria-haspopup="true"
        className="focus:ring-2 focus:ring-blue-500 focus:outline-none"
    >
        <Button>Menu</Button>
    </DropdownMenuTrigger>
</DropdownMenu>
```

**Consistency Checklist**:

- [ ] All buttons use loading state patterns
- [ ] All search inputs use 300ms debouncing
- [ ] All interactive elements have proper ARIA attributes
- [ ] Error handling uses consistent patterns
- [ ] Keyboard navigation works for all components

---

## 📋 Detailed Implementation Plan

### **Day 1: Dashboard Foundation (4 hours)**

#### **1.1 Create DashboardController**

**File**: `app/Http/Controllers/DashboardController.php`

**Purpose**: Central controller handling role-aware dashboard with multi-role widget system.

**Key Methods**:

- `index()` - Main dashboard with role-based widgets
- `admin()` - Admin dashboard with stats and actions

**Multi-Role Widget System**:

```php
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

public function index(Request $request): Response
{
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();

    // Multi-role widget aggregation
    $widgets = [];
    foreach ($userRoles as $role) {
        $widgets = array_merge($widgets, $this->getRoleWidgets($role, $user));
    }

    return Inertia::render('dashboard', [
        'user' => $user,
        'userRoles' => $userRoles,
        'widgets' => $widgets,
    ]);
}

public function admin(Request $request): Response
{
    return Inertia::render('admin/dashboard', [
        'stats' => $this->getAdminStats(),
        'widgets' => $this->getAdminWidgets(),
    ]);
}

private function getRoleWidgets(string $role, $user): array
{
    return match($role) {
        'super_admin' => $this->getAdminWidgets(),
        'coach' => $this->getCoachWidgets(),
        'athlete' => $this->getAthleteWidgets(),
        'parent' => $this->getParentWidgets(),
        'event_organiser' => $this->getEventOrganiserWidgets(),
        'academy_owner' => $this->getAcademyOwnerWidgets(),
        'general_user' => $this->getGeneralUserWidgets(),
        default => []
    };
}

private function getAdminWidgets(): array
{
    return [
        [
            'type' => 'stats',
            'title' => 'System Overview',
            'data' => ['total_users' => '—', 'total_roles' => '—']
        ],
        [
            'type' => 'actions',
            'title' => 'Admin Actions',
            'actions' => [
                ['label' => 'Manage User Roles', 'route' => '/admin/roles'],
                ['label' => 'System Settings', 'route' => '#']
            ]
        ]
    ];
}

private function getCoachWidgets(): array
{
    return [[
        'type' => 'actions',
        'title' => 'Coach Tools',
        'actions' => [
            ['label' => 'My Athletes', 'route' => '#'],
            ['label' => 'Training Programs', 'route' => '#']
        ]
    ]];
}

private function getAthleteWidgets(): array
{
    return [[
        'type' => 'profile',
        'title' => 'My Profile',
        'data' => [
            'name' => auth()->user()->full_name,
            'email' => auth()->user()->email
        ]
    ]];
}

private function getAdminStats(): array
{
    return [
        'total_users' => User::count(),
        'total_roles' => \Spatie\Permission\Models\Role::count(),
        'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
    ];
}
```

#### **1.2 Create User Dashboard Page**

**File**: `resources/js/pages/dashboard.tsx`

**Props Interface**:

```tsx
interface DashboardProps {
    user: {
        id: number;
        first_name: string;
        roles: Array<{ name: string }>;
    };
}
```

**React Implementation with shadcn/ui**:

```tsx
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function Dashboard({ user }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Dashboard</h1>
                    <p className="mt-2 text-muted-foreground">
                        Welcome back, {user.first_name}!
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Your Profile</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Manage your personal information and preferences in
                            the settings.
                        </p>
                        <Button variant="outline" asChild>
                            <Link href="/settings/profile">Go to Settings</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

#### **1.3 Create Admin Dashboard Controller**

**File**: `app/Http/Controllers/Admin/DashboardController.php`

**Admin Dashboard Controller**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'total_users' => '—', // Placeholder for Sprint 4
                'total_roles' => '—',
            ]
        ]);
    }
}
```

#### **1.4 Create Admin Dashboard Page**

**File**: `resources/js/pages/admin/dashboard.tsx`

**Props Interface**:

```tsx
interface DashboardProps {
    user: {
        id: number;
        first_name: string;
        full_name: string;
        email: string;
        roles: Array<{ name: string }>;
    };
    userRoles: string[];
    widgets: Array<{
        type: 'stats' | 'actions' | 'profile' | 'welcome';
        title: string;
        data?: Record<string, any>;
        actions?: Array<{ label: string; route: string }>;
    }>;
}
```

**React Implementation with Multi-Role Widgets**:

```tsx
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface WidgetProps {
    widget: {
        type: 'stats' | 'actions' | 'profile' | 'welcome';
        title: string;
        data?: Record<string, any>;
        actions?: Array<{ label: string; route: string }>;
    };
}

function Widget({ widget }: WidgetProps) {
    const renderWidgetContent = () => {
        switch (widget.type) {
            case 'stats':
                return (
                    <div className="space-y-3">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div key={key} className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        {key
                                            .replace(/_/g, ' ')
                                            .replace(/\b\w/g, (l) =>
                                                l.toUpperCase(),
                                            )}
                                    </span>
                                    <Badge variant="secondary">{value}</Badge>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'actions':
                return (
                    <div className="space-y-2">
                        {widget.actions?.map((action, index) => (
                            <Button
                                key={index}
                                variant="outline"
                                size="sm"
                                className="w-full justify-start"
                                asChild={action.route !== '#'}
                            >
                                {action.route === '#' ? (
                                    <span>{action.label}</span>
                                ) : (
                                    <Link href={action.route}>
                                        {action.label}
                                    </Link>
                                )}
                            </Button>
                        ))}
                    </div>
                );

            case 'profile':
                return (
                    <div className="space-y-2">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div key={key} className="flex flex-col">
                                    <span className="text-sm text-muted-foreground">
                                        {key
                                            .replace(/_/g, ' ')
                                            .replace(/\b\w/g, (l) =>
                                                l.toUpperCase(),
                                            )}
                                    </span>
                                    <span className="font-medium">{value}</span>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'welcome':
                return (
                    <div>
                        <p className="text-sm">{widget.data?.message}</p>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <Card className="widget-transition">
            <CardHeader>
                <CardTitle>{widget.title}</CardTitle>
            </CardHeader>
            <CardContent>{renderWidgetContent()}</CardContent>
        </Card>
    );
}

export default function Dashboard({
    user,
    userRoles,
    widgets,
}: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold">Dashboard</h1>
                    <p className="mt-2 text-muted-foreground">
                        Welcome back, {user.first_name}!
                    </p>
                </div>

                {/* Role Badges */}
                {userRoles.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {userRoles.map((role) => (
                            <Badge
                                key={role}
                                variant="primary"
                                className="role-badge"
                            >
                                {role
                                    .replace(/_/g, ' ')
                                    .replace(/\b\w/g, (l) => l.toUpperCase())}
                            </Badge>
                        ))}
                    </div>
                )}

                {/* Widgets Grid */}
                {widgets.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {widgets.map((widget, index) => (
                            <Widget
                                key={`widget-${widget.type}-${widget.title}-${index}`}
                                widget={widget}
                            />
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-center text-muted-foreground">
                                No widgets available for your current roles.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
```

**React Implementation with shadcn/ui**:

```tsx
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function AdminDashboard({ stats }: AdminDashboardProps) {
    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Admin Dashboard</h1>
                    <p className="mt-2 text-muted-foreground">
                        Administrative controls for Octomat platform.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Management</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Assign and manage user roles.
                            </p>
                            <Button asChild>
                                <Link href="/admin/roles">Manage Roles</Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>System Overview</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Platform statistics and settings.
                            </p>
                            <Button variant="outline" disabled>
                                Coming Soon
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
```

### **Day 1: Dashboard Foundation (4 hours)** (continued)

#### **1.4 Add Frontend Permission Sharing**

**File**: `app/Http/Middleware/HandleInertiaRequests.php` (modify existing)

**Enhancement**: Share user permissions to frontend for client-side authorization.

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user(),
            'roles' => $request->user()?->getRoleNames(),
            'permissions' => $request->user()?->getAllPermissions()->pluck('name')->toArray(),
        ],
    ];
}
```

### **Day 2: Routes & Navigation (3 hours)**

#### **2.1 Add Dashboard Routes**

**File**: `routes/web.php` (modify existing)

**Routes to Add**:

```php
// Main dashboard route (handles role redirects)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin dashboard route (protected)
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])
        ->middleware('can:viewAdminDashboard')
        ->name('admin.dashboard');
});
```

#### **2.2 Update Fortify Redirects**

**File**: `config/fortify.php` (modify existing)

**Changes**:

```php
'redirects' => [
    'login' => '/dashboard', // Will redirect to admin dashboard if super_admin
    'logout' => '/',
    'password-confirmation' => '/dashboard',
    'email-verification' => '/dashboard',
    'two-factor' => '/dashboard',
],
```

#### **2.3 Add Role Assignment Routes**

**File**: `routes/web.php` (modify existing)

**Basic Routes**:

```php
// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/roles', [RoleAssignmentController::class, 'index'])->name('admin.roles');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])->name('admin.roles.assign');
    Route::post('/roles/remove', [RoleAssignmentController::class, 'remove'])->name('admin.roles.remove');
});
```

#### **2.4 Create Role-Aware Navigation Component**

**File**: `resources/js/components/navigation/MainNavigation.tsx` (new)

**Dynamic Navigation Based on User Roles**:

```tsx
import React from 'react';
import { Link, usePage } from '@inertiajs/react';

interface SharedInertiaProps {
    auth: {
        user: any;
        roles: string[];
    };
}

const navigationItems = [
    {
        label: 'Dashboard',
        route: '/dashboard',
        roles: ['*'], // All roles
    },
    {
        label: 'Admin',
        route: '/admin/dashboard',
        roles: ['super_admin'],
    },
    {
        label: 'Events',
        route: '#', // Placeholder for Sprint 4
        roles: ['event_organiser', 'super_admin'],
    },
    {
        label: 'Academy',
        route: '#', // Placeholder for Sprint 4
        roles: ['academy_owner', 'super_admin'],
    },
    {
        label: 'Coaching',
        route: '#', // Placeholder for Sprint 4
        roles: ['coach', 'super_admin'],
    },
    {
        label: 'Family',
        route: '#', // Placeholder for Sprint 4
        roles: ['parent'],
    },
    {
        label: 'My Profile',
        route: '/settings/profile',
        roles: ['*'],
    },
];

function MainNavigation() {
    const { auth } = usePage().props as SharedInertiaProps;
    const userRoles = auth.roles;

    // Filter navigation based on user roles
    const filteredNavigation = navigationItems.filter((item) => {
        if (item.roles.includes('*')) return true;
        return userRoles.some((role) => item.roles.includes(role));
    });

    return (
        <nav className="border-b bg-white shadow-sm">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 justify-between">
                    <div className="flex">
                        <div className="flex flex-shrink-0 items-center">
                            <Link
                                href="/dashboard"
                                className="text-xl font-bold text-gray-900"
                            >
                                Octomat
                            </Link>
                        </div>

                        <div className="hidden sm:ml-6 sm:flex sm:space-x-8">
                            {filteredNavigation.map((item) => (
                                <Link
                                    key={item.route}
                                    href={item.route}
                                    className="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* User Menu */}
                    <div className="flex items-center">
                        <div className="relative">
                            <button className="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                                {auth.user.first_name}
                                <svg
                                    className="ml-2 h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    );
}

export default MainNavigation;
```

#### **2.5 Update App Layout**

**File**: `resources/js/layouts/app-layout.tsx`

**Include Navigation Component**:

```tsx
import MainNavigation from '@/components/navigation/MainNavigation';

export default function AppLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="min-h-screen bg-gray-50">
            <MainNavigation />
            <main className="py-6">{children}</main>
        </div>
    );
}
```

### **Day 3: Role Management Foundation (3 hours)**

#### **3.1 Enhanced Role Assignment Controller with Search**

**File**: `app/Http/Controllers/Admin/RoleAssignmentController.php` (modify existing)

**Enhanced Features** (incorporating archive v05 search functionality):

- Add real-time search functionality
- Add pagination with search query persistence
- Include user role data and filtering
- Add current user context to prevent self-assignment
- Add assign/remove endpoints with basic logging
- Add event dispatching for notifications

**Controller Methods**:

```php
public function index(Request $request)
{
    $query = User::with('roles');

    // Add search functionality (from archive v05)
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    return Inertia::render('admin/role-assignment', [
        'users' => $query->paginate(15)->withQueryString(),
        'filters' => $request->only(['search']),
        'availableRoles' => $this->getAvailableRoles()
    ]);
}

public function assign(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required|string'
    ]);

    $user = User::findOrFail($request->user_id);
    $user->assignRole($request->role);

    // Add event dispatching for notifications (from archive v3)
    Event::dispatch('role.assigned', [
        'user' => $user,
        'role' => $request->role,
        'admin' => auth()->user()
    ]);

    return back()->with('success', 'Role assigned successfully');
}

public function remove(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required|string'
    ]);

    $user = User::findOrFail($request->user_id);
    $user->removeRole($request->role);

    // Add event dispatching for notifications (from archive v3)
    Event::dispatch('role.removed', [
        'user' => $user,
        'role' => $request->role,
        'admin' => auth()->user()
    ]);

    return back()->with('success', 'Role removed successfully');
}

private function getAvailableRoles(): array
{
    return [
        'super_admin' => 'Super Administrator',
        'event_organiser' => 'Event Organiser',
        'academy_owner' => 'Academy Owner',
        'coach' => 'Coach',
        'parent' => 'Parent/Guardian',
        'athlete' => 'Athlete',
        'general_user' => 'General User',
    ];
}
```

#### **3.2 Simple Role Assignment UI**

**File**: `resources/js/pages/admin/role-assignment.tsx`

**Props Interface**:

```tsx
interface RoleAssignmentProps {
    users: {
        data: Array<{
            id: number;
            full_name: string;
            email: string;
            roles: Array<{ name: string }>;
        }>;
        links: any; // Laravel pagination links
    };
    availableRoles: Array<{
        key: string;
        label: string;
    }>;
}
```

**React Implementation with shadcn/ui**:

```tsx
import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Plus, Minus } from 'lucide-react';

export default function RoleAssignment({
    users,
    availableRoles,
}: RoleAssignmentProps) {
    const [loadingActions, setLoadingActions] = useState<
        Record<string, boolean>
    >({});

    const handleAssignRole = (userId: number, roleKey: string) => {
        const actionKey = `assign-${userId}-${roleKey}`;
        setLoadingActions((prev) => ({ ...prev, [actionKey]: true }));

        router.post(
            '/admin/roles/assign',
            {
                user_id: userId,
                role: roleKey,
            },
            {
                preserveScroll: true,
                onFinish: () =>
                    setLoadingActions((prev) => ({
                        ...prev,
                        [actionKey]: false,
                    })),
            },
        );
    };

    const handleRemoveRole = (userId: number, roleName: string) => {
        // Confirmation dialog for destructive action
        if (
            !confirm(`Are you sure you want to remove the '${roleName}' role?`)
        ) {
            return;
        }

        const actionKey = `remove-${userId}-${roleName}`;
        setLoadingActions((prev) => ({ ...prev, [actionKey]: true }));

        router.post(
            '/admin/roles/remove',
            {
                user_id: userId,
                role: roleName,
            },
            {
                preserveScroll: true,
                onFinish: () =>
                    setLoadingActions((prev) => ({
                        ...prev,
                        [actionKey]: false,
                    })),
            },
        );
    };

    const handleRemoveRole = (userId: number, roleName: string) => {
        router.post(
            '/admin/roles/remove',
            {
                user_id: userId,
                role: roleName,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Role Management" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Role Management</h1>
                    <p className="mt-2 text-muted-foreground">
                        Assign and manage user roles for the Octomat platform.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Current Roles</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">
                                            {user.full_name}
                                        </TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((role) => (
                                                    <Badge
                                                        key={role.name}
                                                        variant="secondary"
                                                    >
                                                        {role.name.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </Badge>
                                                ))}
                                                {user.roles.length === 0 && (
                                                    <span className="text-sm text-muted-foreground">
                                                        No roles
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent align="end">
                                                    {user.roles.map((role) => {
                                                        const actionKey = `remove-${user.id}-${role.name}`;
                                                        const isLoading =
                                                            loadingActions[
                                                                actionKey
                                                            ];

                                                        return (
                                                            <DropdownMenuItem
                                                                key={`remove-${role.name}`}
                                                                onClick={() =>
                                                                    !isLoading &&
                                                                    handleRemoveRole(
                                                                        user.id,
                                                                        role.name,
                                                                    )
                                                                }
                                                                disabled={
                                                                    isLoading
                                                                }
                                                                className="text-red-600"
                                                            >
                                                                <Minus className="mr-2 h-4 w-4" />
                                                                {isLoading
                                                                    ? 'Removing...'
                                                                    : `Remove ${role.name.replace('_', ' ')}`}
                                                            </DropdownMenuItem>
                                                        );
                                                    })}
                                                    {user.roles.length > 0 && (
                                                        <DropdownMenuSeparator />
                                                    )}
                                                    {availableRoles.map(
                                                        (role) => {
                                                            const hasRole =
                                                                user.roles.some(
                                                                    (r) =>
                                                                        r.name ===
                                                                        role.key,
                                                                );
                                                            if (hasRole)
                                                                return null;

                                                            const actionKey = `assign-${user.id}-${role.key}`;
                                                            const isLoading =
                                                                loadingActions[
                                                                    actionKey
                                                                ];

                                                            return (
                                                                <DropdownMenuItem
                                                                    key={`add-${role.key}`}
                                                                    onClick={() =>
                                                                        !isLoading &&
                                                                        handleAssignRole(
                                                                            user.id,
                                                                            role.key,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        isLoading
                                                                    }
                                                                    className="text-green-600"
                                                                >
                                                                    <Plus className="mr-2 h-4 w-4" />
                                                                    {isLoading
                                                                        ? 'Adding...'
                                                                        : `Add ${role.label}`}
                                                                </DropdownMenuItem>
                                                            );
                                                        },
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Basic Pagination - will be enhanced in Sprint 4 */}
                        <div className="mt-4">
                            {/* Pagination component would go here */}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

### **Day 4: Testing & Polish (3 hours)**

#### **4.1 Comprehensive Dashboard & Admin Testing**

**File**: `tests/Feature/DashboardAccessTest.php`

**Enhanced Test Coverage** (incorporating archive testing patterns):

#### **Dashboard Access Tests**

````php
test('normal user sees user dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('athlete');

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk()
             ->assertInertia(fn ($inertia) => $inertia
                 ->component('dashboard')
                 ->has('user')
             );
});

test('super_admin redirects to admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertRedirect('/admin/dashboard');
});

test('normal user cannot access admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('athlete');

    $response = $this->actingAs($user)->get('/admin/dashboard');
    $response->assertForbidden();
});

test('role assignment works correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $user = User::factory()->create();

    // Basic assignment test - no logging/events yet
    $response = $this->actingAs($admin)->post('/admin/roles/assign', [
        'user_id' => $user->id,
        'role' => 'coach'
    ]);

    $response->assertRedirect();
    $this->assertTrue($user->fresh()->hasRole('coach'));
});

#### **Search & Filtering Tests**

**File**: `tests/Feature/AdminSearchTest.php`

```php
test('admin can search users by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $user1 = User::factory()->create(['first_name' => 'John']);
    $user2 = User::factory()->create(['first_name' => 'Jane']);

    $response = $this->actingAs($admin)->get('/admin/roles?search=John');

    $response->assertInertia(fn ($inertia) => $inertia
        ->has('users.data', 1)
        ->where('users.data.0.first_name', 'John')
    );
});

test('search is case-insensitive', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $user = User::factory()->create(['email' => 'TEST@example.com']);

    $response = $this->actingAs($admin)->get('/admin/roles?search=test@example.com');

    $response->assertInertia(fn ($inertia) => $inertia
        ->has('users.data', 1)
    );
});

test('search filters persist across requests', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    User::factory()->create(['first_name' => 'Alice']);
    User::factory()->create(['first_name' => 'Bob']);

    $response = $this->actingAs($admin)->get('/admin/roles?search=Alice');
    $response->assertInertia(fn ($inertia) => $inertia
        ->has('users.data', 1)
        ->has('filters.search', 'Alice')
    );
});
````

#### **Event Dispatching Tests**

**File**: `tests/Feature/RoleEventTest.php`

```php
test('role assignment dispatches event', function () {
    Event::fake();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $user = User::factory()->create();

    $this->actingAs($admin)->post('/admin/roles/assign', [
        'user_id' => $user->id,
        'role' => 'coach'
    ]);

    Event::assertDispatched('role.assigned', function ($event) use ($user) {
        return $event['user']->id === $user->id &&
               $event['role'] === 'coach';
    });
});

test('role removal dispatches event', function () {
    Event::fake();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $user = User::factory()->create();
    $user->assignRole('coach');

    $this->actingAs($admin)->post('/admin/roles/remove', [
        'user_id' => $user->id,
        'role' => 'coach'
    ]);

    Event::assertDispatched('role.removed');
});
```

#### **Navigation Tests**

**File**: `tests/Feature/RoleBasedNavigationTest.php`

```php
test('navigation shows role-appropriate items', function () {
    $user = User::factory()->create();
    $user->assignRole('coach');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('Coaching') // Coach should see coaching nav
            ->assertDontSee('Admin'); // Coach should not see admin nav
});

test('multi-role user sees combined navigation', function () {
    $user = User::factory()->create();
    $user->assignRole(['coach', 'event_organiser']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('Coaching') // From coach role
            ->assertSee('Events'); // From event_organiser role
});

test('super_admin sees admin navigation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSee('Admin') // Admin should see admin nav
            ->assertSee('Role Management');
});
```

#### **4.2 Add CSS Transitions and Animations**

**File**: `resources/css/app.css` (modify existing)

**Widget and UI Animations**:

```css
/* Widget hover effects */
.widget-transition {
    transition: all 0.3s ease-in-out;
}

.widget-transition:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Role badge animations */
.role-badge {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Loading states */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
```

#### **4.3 React Component Tests**

**File**: `tests/js/components/Dashboard.test.tsx` (new)

**Basic Component Tests**:

```tsx
test('renders user dashboard with personalized content', () => {
    // Basic rendering tests only
});

test('displays role-appropriate navigation', () => {
    // Basic navigation tests only
});
```

---

## ✅ Success Metrics

### **Functional Requirements (Foundation Level)**

- [ ] Normal users land on `/dashboard` after login
- [ ] Super_admin automatically redirects to `/admin/dashboard`
- [ ] Admin navigation shows role management tools
- [ ] Role assignment UI allows assign/remove operations with table view
- [ ] Access control prevents unauthorized access (403 errors)
- [ ] Dashboard shows basic personalized content based on roles
- [ ] Navigation menu shows appropriate items based on roles

### **Technical Requirements**

- [ ] All routes properly protected with middleware
- [ ] React components receive correct props from controllers
- [ ] TypeScript types properly defined for all data structures
- [ ] Tests pass for all basic access control scenarios (6+ tests)
- [ ] Code follows established patterns and conventions
- [ ] No security regressions introduced

### **Foundation Requirements**

- [ ] Clean, extensible architecture ready for Sprint 4 enhancements
- [ ] No complex features that would block Sprint 4
- [ ] Core RBAC functionality proven to work
- [ ] Ready for incremental addition of advanced features

---

## 📁 Files Created/Modified

### **New Files (12)**

1. `app/Http/Controllers/DashboardController.php` - Multi-role widget system
2. `resources/js/pages/dashboard.tsx` - Dashboard with widget components
3. `resources/js/pages/admin/dashboard.tsx` - Admin dashboard with stats
4. `resources/js/pages/admin/role-assignment.tsx` - Role management UI
5. `resources/js/components/dashboard/Widget.tsx` - Reusable widget component
6. `resources/js/components/navigation/MainNavigation.tsx` - Role-aware navigation
7. `resources/css/app.css` - Widget animations and transitions
8. `tests/Feature/DashboardAccessTest.php` - Access control tests (6 tests)
9. `tests/js/components/Dashboard.test.tsx` - React component tests (3 tests)
10. `tests/Feature/RoleBasedNavigationTest.php` - Navigation tests (2 tests)
11. `tests/Feature/AdminRoleAssignmentTest.php` - Admin UI tests (4 tests)
12. `tests/Feature/MultiRoleWidgetTest.php` - Widget system tests (3 tests)

### **Modified Files (5)**

1. `routes/web.php` - Add dashboard routes with middleware
2. `config/fortify.php` - Update authentication redirects
3. `resources/js/layouts/app-layout.tsx` - Simple role-gated navigation
4. `app/Http/Controllers/Admin/RoleAssignmentController.php` - Basic pagination support
5. `resources/js/pages/admin/role-assignment.tsx` - Table-based UI with dropdowns

---

## 🚀 Sprint Readiness Checklist

### **Pre-Implementation**

- [ ] Sprint 2 role system is fully functional
- [ ] All TypeScript types are properly defined
- [ ] Test infrastructure is working
- [ ] Code style and linting are clean

### **Post-Implementation**

- [ ] All foundation success metrics achieved
- [ ] No complex features implemented (saved for Sprint 4)
- [ ] Tests pass on CI/CD
- [ ] Documentation updated
- [ ] Code review completed

### **Foundation Ready**

- [ ] Security audit passed
- [ ] Performance testing completed
- [ ] Browser compatibility verified
- [ ] Clean architecture ready for Sprint 4 enhancements

---

## 🎯 Why This Foundation Works

### **🏆 Clean Starting Point**

- **No Technical Debt**: Simple, working RBAC UI without over-engineering
- **Proven Concept**: Core role-gated functionality demonstrated
- **Extensible Architecture**: Ready for Sprint 4 complex features
- **Sustainable Pace**: Focused scope prevents feature creep

### **🔄 Sprint 4 Enablement**

This Sprint 3 foundation makes Sprint 4's complex features **effortless to add**:

1. **Search Integration**: Add search field to existing table
2. **Audit Logging**: Add logging calls to existing methods
3. **Notifications**: Add event dispatching to existing actions
4. **Advanced Navigation**: Enhance existing navigation component
5. **Middleware**: Replace controller redirects with custom middleware

### **📈 Business Value Delivered**

- **Immediate ROI**: Working RBAC UI for Sprint 4 product features
- **Risk Reduction**: Core authorization proven before complex features
- **Team Confidence**: Solid foundation for remaining development
- **Quality Assurance**: Thoroughly tested before adding complexity

---

## 🛠️ **React/Inertia.js Foundation**

**Server State Management**: Controllers handle role logic and data provision
**Type Safety**: Full TypeScript interfaces for all data structures
**Component Reusability**: Dashboard components ready for future feature additions
**Performance**: Minimal client-side logic, server-side rendering
**Security**: Permission sharing enables client-side authorization

---

## 🎯 **Sprint 4 Teaser**

With this foundation complete, Sprint 4 can **effortlessly add**:

- 🔍 **Search & Filtering**: Real-time user search in admin
- 📝 **Audit Trails**: Complete role change logging
- ⚡ **Live Notifications**: Toast notifications for actions
- 🎨 **Advanced Navigation**: Dynamic permission-based menus
- 🔧 **Custom Middleware**: Enterprise-grade redirect logic
- 📊 **Real Statistics**: Live dashboard metrics

---

## 🏆 Final Success Signal

**When this statement is true, Sprint 3 foundation is complete:**

_"I can log in as admin and get redirected to admin dashboard with admin tools visible. I can log in as normal user and see user dashboard with appropriate content. Navigation adapts to my role. I can assign/remove roles through a table UI. Non-admins can't access admin pages. The system is ready for Sprint 4's advanced features."_

---

**Status**: ✅ FOUNDATION READY
**Priority**: HIGH
**Dependencies**: Sprint 2 ✅
**Business Value**: RBAC INFRASTRUCTURE
**Effort**: 4 DAYS (LEAN FOUNDATION)
**Next**: Sprint 4 - Complex Feature Completion
