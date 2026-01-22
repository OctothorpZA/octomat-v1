# Architecture & Design Patterns

## Application Architecture

### MVC Architecture with SPA Enhancement

Octomat follows Laravel's traditional MVC pattern enhanced with Inertia.js for single-page application experience.

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Routes        │───▶│  Controllers    │───▶│    Models       │
│                 │    │                 │    │                 │
│ - web.php       │    │ - ProfileCtrl   │    │ - User          │
│ - settings.php  │    │ - PasswordCtrl  │    │ - (Future)      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Views         │    │ Form Requests   │    │  Database       │
│                 │    │                 │    │                 │
│ - React Pages   │    │ - Validation    │    │ - PostgreSQL    │
│ - Components    │    │ - Authorization │    │ - Migrations    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Key Design Patterns

### 1. Form Request Pattern

All form validation is handled through dedicated Form Request classes.

```php
<?php
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
        ];
    }

    public function authorize(): bool
    {
        return auth()->check();
    }
}
```

**Benefits:**

- Separation of validation logic from controllers
- Reusable validation rules
- Automatic validation before controller execution
- Clean controller methods

### 2. Repository Pattern (Future Extension)

```php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function updateProfile(int $id, array $data): User;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function updateProfile(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }
}
```

### 3. Unified Dashboard Pattern

Role-aggregated widget system that dynamically combines content based on user permissions and roles.

```php
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Aggregate widgets from ALL user roles
        $widgets = [];
        foreach ($userRoles as $role) {
            $widgets = array_merge($widgets, $this->getWidgetsForRole($role, $user));
        }

        // Sort by priority and limit
        $widgets = collect($widgets)
            ->sortBy('priority')
            ->take(9)
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'user' => $user,
            'userRoles' => $userRoles,
            'widgets' => $widgets,
        ]);
    }
}
```

**Benefits:**

- Single dashboard for all user types
- Role-based content aggregation
- Scalable widget system
- Clean separation of role-specific logic
- Easy to extend with new roles/widgets

### 4. Service Layer Pattern (Future Extension)

```php
class ProfileService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private NotificationService $notifications
    ) {}

    public function updateProfile(User $user, array $data): User
    {
        $updatedUser = $this->users->update($data);

        if ($updatedUser->isDirty('email')) {
            $this->notifications->sendEmailVerification($updatedUser);
        }

        return $updatedUser;
    }
}
```

### 4. Component Composition Pattern (Frontend)

React components are built using composition over inheritance.

```tsx
// Base component
function Button({ children, variant = 'default', ...props }) {
    return (
        <button className={cn(buttonVariants({ variant }))} {...props}>
            {children}
        </button>
    );
}

// Composed component
function DeleteButton({ onDelete, children }) {
    return (
        <Button variant="destructive" onClick={onDelete}>
            {children}
        </Button>
    );
}
```

## Authentication Patterns

### Laravel Fortify Integration

```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::twoFactorAuthentication(),
],

// Automatic route registration
Fortify::routes();
```

### Middleware Stack

```php
// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Routes requiring authentication
});

// Verified routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Routes requiring email verification
});
```

## Database Patterns

### Migration-First Approach

```php
// Migration: Create table structure
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->timestamps();
});

// Model: Define relationships and casts
class User extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### Eager Loading Pattern

```php
// Prevent N+1 queries
$users = User::with('posts.comments')->get();

// Future relationships
class User extends Authenticatable
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

## Frontend Architecture

### Component Organization

```
components/
├── ui/           # Reusable UI components (shadcn/ui)
├── forms/        # Form-specific components
├── layout/       # Layout components
├── auth/         # Authentication components
└── settings/     # Settings-specific components
```

### State Management Strategy

```tsx
// Local component state
const [isLoading, setIsLoading] = useState(false);

// Server state via Inertia
function ProfilePage({ user, errors }) {
    // Props from Laravel controller
}

// Custom hooks for shared state
const [theme, setTheme] = useAppearance();
```

### Route-Based Code Splitting

```tsx
// Automatic code splitting by Inertia
const pages = import.meta.glob('./pages/**/*.tsx');

// Lazy loading by default
function resolvePageComponent(name: string) {
    return pages[`./pages/${name}.tsx`]();
}
```

## Error Handling Patterns

### Frontend Error Boundaries

```tsx
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Error caught by boundary:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return <ErrorFallback />;
        }

        return this.props.children;
    }
}
```

### Backend Exception Handling

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->is('api/*')) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }

    return parent::render($request, $exception);
}
```

## Testing Patterns

### Test Organization

```php
// Feature tests for user workflows
test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('New Name');
});

// Unit tests for business logic
test('user factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBeString();
});
```

### Factory Pattern for Test Data

```php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    // States for different scenarios
    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
```

## Performance Patterns

### Database Optimization

```php
// Use indexes for frequently queried columns
$table->index('email');
$table->index(['created_at', 'updated_at']);

// Avoid N+1 queries
User::with('posts')->get();

// Use pagination for large datasets
User::paginate(20);
```

### Frontend Optimization

```tsx
// Lazy loading
const Component = lazy(() => import('./Component'));

// Memoization
const MemoizedComponent = memo(Component);

// Code splitting
const pages = import.meta.glob('./pages/**/*.tsx');
```

## Security Patterns

### Mass Assignment Protection

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'two_factor_secret'];
}
```

### CSRF Protection

```php
// Automatic CSRF protection on all forms
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

### Rate Limiting

```php
// Route-level rate limiting
Route::put('settings/password', [PasswordController::class, 'update'])
    ->middleware('throttle:6,1'); // 6 requests per minute
```

## Configuration Patterns

### Environment-Based Configuration

```php
// config/app.php
'debug' => env('APP_DEBUG', false),

// config/database.php
'default' => env('DB_CONNECTION', 'pgsql'),
```

### Service Configuration

```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::twoFactorAuthentication(),
],
```

## Future Architecture Extensions

### API Versioning

```php
// Future API routes
Route::prefix('api/v1')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

### Event-Driven Architecture

```php
// Events for decoupled communication
class UserProfileUpdated
{
    public function __construct(public User $user) {}
}

// Listeners for side effects
class SendProfileUpdateNotification
{
    public function handle(UserProfileUpdated $event): void
    {
        // Send notification
    }
}
```

### CQRS Pattern

```php
// Command for writes
class UpdateUserProfile
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}

// Query for reads
class GetUserProfile
{
    public function __construct(public int $userId) {}
}
```

This architecture provides a solid foundation that can scale with the application's needs while maintaining clean, testable, and maintainable code.</content>
<parameter name="filePath">docs/RAG/architecture-patterns.md
