# Frontend Components & Pages

## React Component Structure

### Pages (`resources/js/pages/`)

Main application pages rendered by Inertia.js.

#### Authentication Pages

- `welcome.tsx` - Landing page with registration toggle
- `auth/login.tsx` - Login form
- `auth/register.tsx` - Registration form
- `auth/forgot-password.tsx` - Password reset request
- `auth/reset-password.tsx` - Password reset form
- `auth/confirm-password.tsx` - Password confirmation
- `auth/two-factor-challenge.tsx` - 2FA verification
- `auth/verify-email.tsx` - Email verification notice

#### Main Application Pages

- `dashboard.tsx` - User dashboard
- `settings/profile.tsx` - Profile settings
- `settings/password.tsx` - Password change
- `settings/appearance.tsx` - Theme settings
- `settings/two-factor.tsx` - 2FA management

#### Admin Pages

- `admin/role-assignment.tsx` - Role assignment interface for administrators

### Layouts (`resources/js/layouts/`)

Reusable layout components.

#### Authentication Layouts

- `auth-layout.tsx` - Base auth layout
- `auth/auth-split-layout.tsx` - Split-screen auth layout
- `auth/auth-simple-layout.tsx` - Simple auth layout
- `auth/auth-card-layout.tsx` - Card-based auth layout

#### Application Layouts

- `app-layout.tsx` - Main app layout with sidebar
- `app/app-header-layout.tsx` - Header-only layout
- `app/app-sidebar-layout.tsx` - Sidebar layout
- `settings/layout.tsx` - Settings page layout

### Components (`resources/js/components/`)

Reusable UI components organized by functionality.

#### UI Components (`components/ui/`)

Shadcn/ui-based components with Tailwind CSS:

- `button.tsx` - Button component with variants
- `input.tsx` - Input field component
- `select.tsx` - Select dropdown component
- `dialog.tsx` - Modal dialog component
- `badge.tsx` - Status badge component
- `card.tsx` - Card container component
- `alert.tsx` - Alert/notification component
- `skeleton.tsx` - Loading skeleton component

#### Feature Components

- `alert-error.tsx` - Error display component
- `input-error.tsx` - Form field error display
- `text-link.tsx` - Styled text link component
- `icon.tsx` - Icon wrapper component
- `heading.tsx` - Page heading component
- `heading-small.tsx` - Section heading component

#### Authentication Components

- `delete-user.tsx` - Account deletion form
- `two-factor-setup-modal.tsx` - 2FA setup modal
- `two-factor-recovery-codes.tsx` - Recovery codes display
- `user-info.tsx` - User information display

#### Navigation Components

- `app-header.tsx` - Application header
- `app-sidebar.tsx` - Application sidebar
- `nav-main.tsx` - Main navigation
- `nav-user.tsx` - User menu
- `nav-footer.tsx` - Footer navigation
- `breadcrumbs.tsx` - Navigation breadcrumbs

#### Layout Components

- `app-shell.tsx` - Main application shell
- `app-content.tsx` - Main content area
- `app-logo.tsx` - Application logo
- `app-logo-icon.tsx` - Logo icon component

#### Theme Components

- `appearance-tabs.tsx` - Theme selection tabs
- `appearance-dropdown.tsx` - Theme dropdown menu

### Hooks (`resources/js/hooks/`)

Custom React hooks for shared logic.

- `use-appearance.tsx` - Theme management hook
- `use-initials.tsx` - User initials generation
- `use-mobile.tsx` - Mobile detection hook

## Key Component Patterns

### Button Component

```tsx
import { Button, buttonVariants } from '@/components/ui/button';

// Usage
<Button variant="default" size="default">
    Click me
</Button>;

// Variants: default, destructive, outline, secondary, ghost, link
// Sizes: default, sm, lg, icon
```

### Form Components

```tsx
import { Form } from '@inertiajs/react';

<Form action="/users" method="post">
    {({ errors, processing }) => (
        <>
            <input name="name" />
            {errors.name && <div>{errors.name}</div>}
            <Button disabled={processing}>Submit</Button>
        </>
    )}
</Form>;
```

### Layout Pattern

```tsx
import AppLayout from '@/layouts/app-layout';

export default function Dashboard() {
    return (
        <AppLayout>
            <div className="space-y-6">
                <Heading>Dashboard</Heading>
                {/* Content */}
            </div>
        </AppLayout>
    );
}
```

## Styling Patterns

### Tailwind CSS Classes

- Utility-first approach
- Responsive design with breakpoint prefixes
- Dark mode support with `dark:` prefix
- Custom CSS variables for theming

### Component Variants

```tsx
const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,box-shadow] disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default:
                    'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90',
                destructive:
                    'bg-destructive text-white shadow-xs hover:bg-destructive/90',
            },
            size: {
                default: 'h-9 px-4 py-2',
                sm: 'h-8 rounded-md px-3',
                lg: 'h-10 rounded-md px-6',
            },
        },
    },
);
```

## Navigation Patterns

### Inertia.js Navigation

```tsx
import { Link } from '@inertiajs/react';

<Link href="/dashboard">Dashboard</Link>;
```

### Programmatic Navigation

```tsx
import { router } from '@inertiajs/react';

router.visit('/dashboard');
```

## State Management

- **Local Component State**: React useState/useReducer
- **Server State**: Inertia.js props from Laravel
- **Theme State**: Custom use-appearance hook with localStorage
- **Form State**: Inertia.js Form helper

## TypeScript Integration

- Strict type checking enabled
- Interface definitions for component props
- Type-safe Inertia props
- Utility types for variants and configurations

## Performance Optimizations

- React Compiler (babel-plugin-react-compiler)
- Tree-shaking with named imports
- Lazy loading for routes
- Optimized bundle splitting with Vite</content>
  <parameter name="filePath">docs/RAG/frontend-components.md
