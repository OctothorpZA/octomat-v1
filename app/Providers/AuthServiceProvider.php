<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-academies', function (User $user): bool {
            return $user->hasRole('Academy Owner');
        });

        Gate::define('manage-events', function (User $user): bool {
            return $user->hasRole('Event Organiser');
        });

        Gate::define('system-admin', function (User $user): bool {
            return $user->hasRole('Super Admin');
        });

        Gate::define('assign-roles', function (User $user): bool {
            return $user->hasRole('Super Admin');
            // return $user->hasAnyRole([
            //     'Super Admin',
            //     'Federation Admin', // May need to assign roles within their federation
            //     'Academy Owner', // May need to assign roles within their academy
            //     'Club Manager', // May need to assign roles within their club
            //     'Club Admin', // May need to assign roles within their club
            // ]);
        });
    }
}
