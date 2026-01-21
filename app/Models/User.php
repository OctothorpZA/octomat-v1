<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Mirror\Concerns\Impersonatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Impersonatable, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_names',
        'last_name',
        'date_of_birth',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date:Y-m-d',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's full name (backward compatibility - First + Last only).
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

    /**
     * Get the user's initials (First + Last).
     */
    public function initials(): string
    {
        $initials = Str::of(($this->first_name ?? '').' '.($this->last_name ?? ''))
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');

        return (string) Str::upper($initials);
    }

    /**
     * Get user's age in years.
     * Note: date_of_birth is already a Carbon instance thanks to casting.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Get formatted date of birth for display (privacy-conscious).
     */
    public function getFormattedDateOfBirthAttribute(): ?string
    {
        return $this->date_of_birth?->format('Y-m-d');
    }

    /**
     * Get date of birth formatted for HTML date inputs.
     */
    public function getDateOfBirthForFormAttribute(): ?string
    {
        return $this->date_of_birth?->format('Y-m-d');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is a minor (under 18).
     */
    public function isMinor(): bool
    {
        $age = $this->getAgeAttribute();

        return $age !== null && $age < 18;
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Custom helper methods for role management
     */
    public function getHighestRoleLevel(): int
    {
        return $this->roles()->max('level') ?? 300;
    }

    public function getPrimaryRole(): ?Role
    {
        return $this->roles()
            ->orderByDesc('level')
            ->first();
    }

    /**
     * Auto-assign default role
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Guard against seeder not running yet (safer approach)
            if (! $user->roles()->exists()) {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'General User']);
                $user->assignRole($role);
            }
        });
    }

    /**
     * Add fallback role creation to prevent roleless users
     */
    protected function assignDefaultRole(): void
    {
        if (! $this->roles()->exists()) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'General User']);
            $this->assignRole($role);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Impersonation Authorization Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if the user can impersonate others.
     * Only Super Admin can impersonate.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Determine if the user can be impersonated by the given impersonator.
     * Super admins cannot be impersonated, but all other users can.
     */
    public function canBeImpersonatedBy(User $impersonator): bool
    {
        return ! $this->hasRole('Super Admin');
    }
}
