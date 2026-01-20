# Database Schema & Models

## Users Table

Primary user model with authentication and two-factor authentication support.

### Schema

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    middle_names VARCHAR(255) NULL,
    last_name VARCHAR(255) NOT NULL,
    date_of_birth DATE NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

### Model: `App\Models\User`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'first_name',
        'middle_names',
        'last_name',
        'date_of_birth',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token'
    ];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date:Y-m-d',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's full name (backward compatibility).
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the user's full name using the clean array_filter approach.
     */
    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_names,
            $this->last_name,
        ]));
    }
}
```

## Supporting Tables

### Password Reset Tokens

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

### Sessions

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL
);
```

### Jobs Table (Queue Support)

```sql
CREATE TABLE jobs (
    id SERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL
);
```

### Cache Table

Standard Laravel cache table structure for application caching.

## Relationships

- **User**: Central model with no explicit relationships defined yet
- All authentication-related functionality is handled through Laravel Fortify

## Factories

- **UserFactory**: Generates test users with structured name fields and faker data
- Includes proper password hashing, email verification states, and realistic DOB ranges
- 70% chance of DOB in 13-70 year age range, 30% chance of middle names

## Key Patterns

- **Soft Deletes**: Implemented for user safety
- **Structured Name Fields**: first_name, middle_names (nullable), last_name with computed accessors
- **Date of Birth**: Nullable date field with Y-m-d serialization format
- **Backward Compatibility**: `name` accessor maintains old API compatibility
- **Full Name Accessor**: `full_name` includes middle names with proper filtering
- **Timestamps**: Standard Laravel created_at/updated_at
- **UUID**: Not used (incrementing IDs)
- **Polymorphic Relations**: None currently defined
- **Eager Loading**: Not needed for current user relationships

## Migration Order

1. `0001_01_01_000000_create_users_table.php` - Base user table
2. `0001_01_01_000001_create_cache_table.php` - Cache support
3. `0001_01_01_000002_create_jobs_table.php` - Queue support
4. `2025_08_26_100418_add_two_factor_columns_to_users_table.php` - 2FA fields</content>
   <parameter name="filePath">docs/RAG/database-schema.md
