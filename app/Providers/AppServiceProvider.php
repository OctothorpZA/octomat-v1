<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Mirror\Events\ImpersonationStarted;
use Mirror\Events\ImpersonationStopped;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureImpersonationEvents();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureImpersonationEvents(): void
    {
        // Mirror impersonation events for audit logging
        Event::listen(ImpersonationStarted::class, function (ImpersonationStarted $event) {
            // Log impersonation start for audit trail
            logger()->info('User impersonation started', [
                'impersonator_id' => $event->impersonator->id,
                'impersonator_email' => $event->impersonator->email,
                'impersonated_id' => $event->impersonated->id,
                'impersonated_email' => $event->impersonated->email,
                'guard' => $event->guardName,
                'timestamp' => now()->toISOString(),
            ]);

            // TODO: Sprint 4 - Store in audit log database table
        });

        Event::listen(ImpersonationStopped::class, function (ImpersonationStopped $event) {
            // Log impersonation stop for audit trail
            logger()->info('User impersonation stopped', [
                'impersonator_id' => $event->impersonator->id,
                'impersonator_email' => $event->impersonator->email,
                'impersonated_id' => $event->impersonated->id,
                'impersonated_email' => $event->impersonated->email,
                'guard' => $event->guardName,
                'timestamp' => now()->toISOString(),
            ]);

            // TODO: Sprint 4 - Store in audit log database table
        });
    }
}
