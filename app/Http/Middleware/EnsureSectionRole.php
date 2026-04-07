<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSectionRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $section = app()->bound('currentSection') ? app('currentSection') : 'dolfijnen';

        if (! $user || ! $user->hasRoleInSection($section, $roles)) {
            abort(403, 'Je hebt geen toegang tot deze speltak of actie.');
        }

        // Oudercontact is standaard alleen-lezen.
        if (
            $user->hasRoleInSection($section, ['ouder_contact']) &&
            ! $user->hasRoleInSection($section, ['teamleider', 'leiding']) &&
            ! in_array($request->method(), ['GET', 'HEAD'], true)
        ) {
            abort(403, 'Alleen teamleider of leiding mag wijzigingen doen.');
        }

        return $next($request);
    }
}
