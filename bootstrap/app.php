<?php

use App\Http\Middleware\EnsureSectionPermission;
use App\Http\Middleware\EnsureSectionRole;
use App\Http\Middleware\EnsureUserHasAnyRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RememberInertiaReturnUrl;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrentSection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Respect X-Forwarded-* headers from reverse proxies/load balancers.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            SetCurrentSection::class,
            RememberInertiaReturnUrl::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'section.role' => EnsureSectionRole::class,
            'section.permission' => EnsureSectionPermission::class,
            'has.role' => EnsureUserHasAnyRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();
            $view = "errors.$status";
            if (view()->exists($view)) {
                return response()->view($view, [], $status);
            }

            return response()->view('errors.generic', ['status' => $status], $status);
        });
    })->create();
