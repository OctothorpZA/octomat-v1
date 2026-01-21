<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mirror\Facades\Mirror;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        // Mirror will handle the impersonation and return a token
        $token = Mirror::start($user);

        // Redirect back to dashboard (or wherever the user was)
        return redirect()->intended('/dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        Mirror::stop();

        return redirect('/dashboard');
    }
}
