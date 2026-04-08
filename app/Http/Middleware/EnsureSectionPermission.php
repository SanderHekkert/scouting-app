<?php

namespace App\Http\Middleware;

use App\Services\SectionPermissionGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSectionPermission
{
    public function __construct(
        private readonly SectionPermissionGate $gate
    ) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $section = app()->bound('currentSection') ? app('currentSection') : 'dolfijnen';
        $action = $this->actionFromMethod($request->method());

        if (! $this->gate->allows($request->user(), $section, $module, $action)) {
            abort(403, 'Je hebt geen rechten voor deze actie.');
        }

        return $next($request);
    }

    private function actionFromMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD' => 'view',
            'POST' => 'create',
            'PATCH', 'PUT' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}
