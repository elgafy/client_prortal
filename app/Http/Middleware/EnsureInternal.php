<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Internal routes (clients, projects, payments, reports) are for
 * administrators and staff only. Client-role users are redirected
 * to their portal.
 */
class EnsureInternal
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isClient()) {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
