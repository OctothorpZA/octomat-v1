# Configuration & Environment

## Environment Configuration

### .env File Structure

```bash
# Application
APP_NAME="Octomat"
APP_ENV=local
APP_KEY=base64:generated-key
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=octomat
DB_USERNAME=postgres
DB_PASSWORD=password

# Cache & Sessions
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# Broadcasting (future)
BROADCAST_CONNECTION=log

# Logging
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Vite
VITE_APP_NAME="${APP_NAME}"
```

## Laravel Configuration Files

### config/app.php

```php
<?php
return [
    'name' => env('APP_NAME', 'Laravel'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'providers' => [
        // Laravel Framework Service Providers
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        // Package Service Providers
        Laravel\Fortify\FortifyServiceProvider::class,
        Laravel\Sanctum\SanctumServiceProvider::class,
        Inertia\ServiceProvider::class,
        Laravel\Telescope\TelescopeServiceProvider::class, // Optional
    ],
];
```

### config/fortify.php

```php
<?php
use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'auth_middleware' => 'auth',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'views' => false,
    'home' => '/dashboard',

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirmPassword' => true,
            'confirm' => true,
        ]),
    ],

    'redirects' => [
        'login' => '/dashboard',
        'logout' => '/',
        'password-confirmation' => '/dashboard',
        'register' => '/dashboard',
        'email-verification' => '/dashboard',
        'password-reset' => '/login',
    ],
];
```

### config/laravel-impersonate.php

Laravel Impersonate package configuration for secure admin user impersonation.

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Take Route
    |--------------------------------------------------------------------------
    |
    | The route that will be used to take impersonation.
    |
    */

    'take_route' => [
        'name' => 'impersonate',
        'method' => 'POST',
        'uri' => '/impersonate/{id}/{guardName?}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Leave Route
    |--------------------------------------------------------------------------
    |
    | The route that will be used to leave impersonation.
    |
    */

    'leave_route' => [
        'name' => 'impersonate.leave',
        'method' => 'POST',
        'uri' => '/impersonate/leave',
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonator Guard
    |--------------------------------------------------------------------------
    |
    | The guard that will be used to authenticate the impersonator.
    | This guard will be used to retrieve the impersonator user.
    |
    */

    'impersonator_guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Impersonated Guard
    |--------------------------------------------------------------------------
    |
    | The guard that will be used to authenticate the impersonated user.
    | This guard will be used to retrieve the impersonated user.
    |
    */

    'impersonated_guard' => 'web',
];
```

### config/database.php

```php
<?php
return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
];
```

## Vite Configuration

### vite.config.ts

```typescript
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
```

## ESLint Configuration

### eslint.config.js

```javascript
import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';

export default [
    js.configs.recommended,
    reactHooks.configs.flat.recommended,
    ...typescript.configs.recommended,
    {
        ...react.configs.flat.recommended,
        ...react.configs.flat['jsx-runtime'],
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
    {
        ...importPlugin.flatConfigs.recommended,
        settings: {
            'import/resolver': {
                typescript: true,
                node: true,
            },
        },
        rules: {
            'import/order': [
                'error',
                {
                    groups: [
                        'builtin',
                        'external',
                        'internal',
                        'parent',
                        'sibling',
                        'index',
                    ],
                    'newlines-between': 'always',
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
        },
    },
    {
        ...importPlugin.flatConfigs.typescript,
        files: ['**/*.{ts,tsx}'],
    },
    {
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
        ],
    },
    prettier,
];
```

## Prettier Configuration

### .prettierrc

```json
{
    "semi": true,
    "singleQuote": true,
    "singleAttributePerLine": false,
    "htmlWhitespaceSensitivity": "css",
    "printWidth": 80,
    "plugins": [
        "prettier-plugin-organize-imports",
        "prettier-plugin-tailwindcss"
    ],
    "tailwindFunctions": ["clsx", "cn"],
    "tailwindStylesheet": "resources/css/app.css",
    "tabWidth": 4,
    "overrides": [
        {
            "files": "**/*.yml",
            "options": {
                "tabWidth": 2
            }
        }
    ]
}
```

## Laravel Structure (Laravel 12)

### bootstrap/app.php

```php
<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
```

### bootstrap/providers.php

```php
<?php
return [
    App\Providers\AppServiceProvider::class,
    // Laravel Fortify automatically registered
];
```

## Package Versions

### Composer Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "inertiajs/inertia-laravel": "^2.0",
        "laravel/fortify": "^1.30",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1",
        "laravel/wayfinder": "^0.1.9"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/boost": "^1.8",
        "laravel/pail": "^1.2.2",
        "laravel/pint": "^1.24",
        "laravel/sail": "^1.41",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "pestphp/pest": "^4.3",
        "pestphp/pest-plugin-laravel": "^4.0"
    }
}
```

### NPM Dependencies

```json
{
    "dependencies": {
        "@headlessui/react": "^2.2.0",
        "@inertiajs/react": "^2.3.7",
        "@radix-ui/react-avatar": "^1.1.3",
        "@radix-ui/react-checkbox": "^1.1.4",
        "@radix-ui/react-collapsible": "^1.1.3",
        "@radix-ui/react-dialog": "^1.1.6",
        "@radix-ui/react-dropdown-menu": "^2.1.6",
        "@radix-ui/react-label": "^2.1.2",
        "@radix-ui/react-navigation-menu": "^1.2.5",
        "@radix-ui/react-select": "^2.1.6",
        "@radix-ui/react-separator": "^1.1.2",
        "@radix-ui/react-slot": "^1.2.3",
        "@radix-ui/react-toggle": "^1.1.2",
        "@radix-ui/react-toggle-group": "^1.1.2",
        "@radix-ui/react-tooltip": "^1.1.8",
        "@tailwindcss/vite": "^4.1.11",
        "@types/react": "^19.2.0",
        "@types/react-dom": "^19.2.0",
        "@vitejs/plugin-react": "^5.0.0",
        "class-variance-authority": "^0.7.1",
        "clsx": "^2.1.1",
        "concurrently": "^9.0.1",
        "globals": "^15.14.0",
        "input-otp": "^1.4.2",
        "laravel-vite-plugin": "^2.0",
        "lucide-react": "^0.475.0",
        "react": "^19.2.0",
        "react-dom": "^19.2.0",
        "tailwind-merge": "^3.0.1",
        "tailwindcss": "^4.0.0",
        "tw-animate-css": "^1.4.0",
        "typescript": "^5.7.2",
        "vite": "^7.0.4"
    },
    "devDependencies": {
        "@eslint/js": "^9.19.0",
        "@laravel/vite-plugin-wayfinder": "^0.1.3",
        "@types/node": "^22.13.5",
        "babel-plugin-react-compiler": "^1.0.0",
        "eslint": "^9.17.0",
        "eslint-config-prettier": "^10.0.1",
        "eslint-import-resolver-typescript": "^4.4.4",
        "eslint-plugin-import": "^2.32.0",
        "eslint-plugin-react": "^7.37.3",
        "eslint-plugin-react-hooks": "^7.0.0",
        "prettier": "^3.4.2",
        "prettier-plugin-organize-imports": "^4.1.0",
        "prettier-plugin-tailwindcss": "^0.6.11",
        "typescript-eslint": "^8.23.0"
    }
}
```

## Deployment Configuration

### Production Environment Variables

```bash
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=production_db
DB_USERNAME=production_user
DB_PASSWORD=secure_password

# Cache & Performance
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# Security
APP_KEY=your-generated-app-key
```

### Nginx Configuration (Example)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### SSL Configuration (Let's Encrypt)

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # ... rest of config
}
```

## Monitoring & Logging

### Laravel Logging Configuration

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'daily'],
    ],

    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

### Application Monitoring

```bash
# Laravel Telescope (optional)
composer require laravel/telescope
php artisan telescope:install
php artisan migrate

# Laravel Pulse (optional)
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

This configuration provides a solid foundation for both development and production environments, ensuring consistent behavior across different deployment scenarios.</content>
<parameter name="filePath">docs/RAG/configuration.md
