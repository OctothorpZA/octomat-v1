<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonateProtection
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Only super_admin can impersonate - check the impersonator's permissions
        if (app(ImpersonateManager::class)->isImpersonating()) {
            $impersonator = app(ImpersonateManager::class)->getImpersonator();
            if (! $impersonator || ! $impersonator->hasRole('Super Admin')) {
                abort(403, 'Unauthorized impersonation attempt');
            }
        }

        return $next($request);
    }
}
