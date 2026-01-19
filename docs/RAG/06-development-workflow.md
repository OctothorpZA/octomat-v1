# Development Workflow & Commands

## Local Development Setup

### Initial Setup

```bash
# Clone repository
git clone <repository-url>
cd octomat

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Build frontend assets
npm run build
```

### Alternative Setup (Composer Script)

```bash
composer run setup
```

## Development Servers

### Full Development Environment

```bash
composer run dev
```

This command runs:

- Laravel server (port 8000)
- Queue worker (tries=1)
- Application logs (pail)
- Vite dev server (port 5173)

### SSR Development

```bash
composer run dev:ssr
```

Runs SSR-enabled development environment.

### Individual Services

```bash
# Laravel server only
php artisan serve

# Frontend development
npm run dev

# Queue worker
php artisan queue:listen --tries=1

# Log monitoring
php artisan pail --timeout=0
```

## Code Quality & Linting

### PHP Code Quality

```bash
# Run PHP linter (Laravel Pint)
composer run lint

# Check linting without fixing
composer run test:lint

# Format PHP code
vendor/bin/pint --dirty
```

### JavaScript/TypeScript Quality

```bash
# Lint and fix code
npm run lint

# Check formatting
npm run format:check

# Format code
npm run format

# Type checking
npm run types
```

## Testing Workflow

### Running Tests

```bash
# Run all tests
composer run test

# Run tests in compact mode
php artisan test --compact

# Run specific test file
php artisan test --compact tests/Feature/DashboardTest.php

# Run specific test method
php artisan test --compact --filter="authenticated users can visit the dashboard"

# Run unit tests only
php artisan test tests/Unit

# Run feature tests only
php artisan test tests/Feature
```

### Test-Driven Development

1. Write failing test
2. Implement feature
3. Run tests to verify
4. Refactor if needed
5. Run full test suite

## Build & Deployment

### Frontend Building

```bash
# Production build
npm run build

# SSR build
npm run build:ssr

# Build with analysis
npm run build -- --mode analyze
```

### Asset Management

```bash
# Clear compiled assets
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Rebuild all assets
npm run build && php artisan config:cache
```

## Database Operations

### Migrations

```bash
# Run pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Create new migration
php artisan make:migration create_example_table

# Status of migrations
php artisan migrate:status
```

### Seeders & Factories

```bash
# Run seeders
php artisan db:seed

# Create new seeder
php artisan make:seeder ExampleSeeder

# Create new factory
php artisan make:factory ExampleFactory
```

## Authentication & Security

### Laravel Fortify Features

```php
// Check enabled features in config/fortify.php
Features::registration()     // User registration
Features::resetPasswords()   // Password reset
Features::emailVerification() // Email verification
Features::twoFactorAuthentication() // 2FA
```

### User Management

```bash
# Create user (via tinker)
php artisan tinker
>>> User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => Hash::make('password')])
```

## Queue & Background Jobs

### Queue Management

```bash
# Start queue worker
php artisan queue:work

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Debugging & Monitoring

### Laravel Debug Tools

```bash
# Laravel Telescope (if installed)
php artisan telescope:install

# Laravel Debugbar (if installed)
# Automatically enabled in local environment

# Application logs
php artisan pail
php artisan pail --timeout=0
```

### Browser Developer Tools

- React DevTools for component inspection
- Network tab for API calls
- Console for JavaScript errors
- Application tab for localStorage/sessionStorage

## Git Workflow

### Pre-commit Hooks

```bash
# PHP formatting (automatic)
vendor/bin/pint --dirty

# Run tests before committing
composer run test
```

### Conventional Commits

```
feat: add user profile page
fix: resolve password reset bug
docs: update API documentation
style: format code with prettier
refactor: extract user service class
test: add profile update tests
```

## Environment Management

### Environment Files

```bash
# Local development
.env                # Gitignored, local config

# Production
.env.production     # Production config
.env.staging        # Staging config
```

### Configuration Caching

```bash
# Cache config for production
php artisan config:cache

# Clear cache for development
php artisan config:clear
```

## Performance Optimization

### Frontend Optimization

```bash
# Bundle analysis
npm run build -- --mode analyze

# Build for production
npm run build

# Enable SSR
npm run build:ssr
```

### Backend Optimization

```bash
# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache config
php artisan config:cache

# Clear all caches
php artisan optimize:clear
```

## Troubleshooting

### Common Issues

#### Frontend Changes Not Reflecting

```bash
# Clear Vite cache
rm -rf node_modules/.vite

# Rebuild assets
npm run build

# Restart dev server
composer run dev
```

#### Database Connection Issues

```bash
# Check database configuration
php artisan tinker
>>> config('database.default')

# Test database connection
php artisan migrate:status
```

#### Permission Issues

```bash
# Fix storage permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Fix node_modules permissions
chown -R $USER:$USER node_modules
```

#### Port Conflicts

```bash
# Check running processes
lsof -i :8000  # Laravel
lsof -i :5173  # Vite

# Kill process
kill -9 <PID>
```

## Deployment Checklist

### Pre-deployment

- [ ] Run full test suite: `composer run test`
- [ ] Code quality checks: `composer run lint && npm run lint`
- [ ] Build assets: `npm run build`
- [ ] Database migrations: `php artisan migrate`
- [ ] Clear caches: `php artisan optimize:clear`

### Post-deployment

- [ ] Verify application loads
- [ ] Test authentication flow
- [ ] Test critical user paths
- [ ] Monitor error logs
- [ ] Check queue processing

## Continuous Integration

### GitHub Actions (Example)

```yaml
name: CI
on: [push, pull_request]
jobs:
    test:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v2
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.5'
            - name: Install dependencies
              run: composer install
            - name: Run tests
              run: composer run test
```

This workflow ensures code quality and prevents breaking changes from being merged.</content>
<parameter name="filePath">docs/RAG/development-workflow.md
