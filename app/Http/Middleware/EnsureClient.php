<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal routes are for client-role users only. Internal users are
 * redirected to the internal dashboard.
 */
class EnsureClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isClient()) {
            return redirect()->route('dashboard');
        }

        // A client user must be linked to a client account.
        if (! $user->client_id) {
            Auth::guard('web')->logout();

            return redirect()->route('login')->withErrors([
                'email' => __('This account is not linked to a client.'),
            ]);
        }

        return $next($request);
    }
}
