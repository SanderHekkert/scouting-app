<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberInertiaReturnUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethod('GET')
            && $request->header('X-Inertia')
            && ! $request->header('X-Inertia-Partial-Data')
        ) {
            $previous = url()->previous();
            $current = $request->fullUrl();
            $allowedHosts = array_values(array_filter(array_unique([
                parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_HOST),
                parse_url($current, PHP_URL_HOST),
            ])));
            $previousHost = is_string($previous) ? parse_url($previous, PHP_URL_HOST) : null;

            if (
                is_string($previous)
                && $previousHost !== null
                && in_array($previousHost, $allowedHosts, true)
                && rtrim($previous, '/') !== rtrim($current, '/')
            ) {
                $request->session()->put('inertia_return_url', $previous);
            }
        }

        return $next($request);
    }
}
