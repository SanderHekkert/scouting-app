<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAnyRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('no-access') || $request->routeIs('logout')) {
            return $next($request);
        }

        $hasRole = $user->sectionRoles()->exists();
        if (! $hasRole) {
            return redirect()->route('no-access');
        }

        return $next($request);
    }
}
