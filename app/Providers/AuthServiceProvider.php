<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-academies', function ($user) {
            return $user->hasRole('Academy Owner');
        });

        Gate::define('manage-events', function ($user) {
            return $user->hasRole('Event Organiser');
        });

        Gate::define('system-admin', function ($user) {
            return $user->hasRole('Super Admin');
        });

        Gate::define('assign-roles', function ($user) {
            return $user->hasAnyRole([
                'Super Admin',
                'Federation Admin',
                'Academy Owner',
                'Club Manager',
                'Club Admin',
            ]);
        });
    }
}
