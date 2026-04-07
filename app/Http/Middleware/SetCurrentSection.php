<?php

namespace App\Http\Middleware;

use App\Models\UserSectionRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentSection
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $default = UserSectionRole::SECTION_DOLFIJNEN;

        if (! $user) {
            app()->instance('currentSection', $default);

            return $next($request);
        }

        $requested = $request->session()->get('active_section', $default);
        $hasGlobalAdmin = $user->sectionRoles()
            ->where('section', UserSectionRole::SECTION_ALL)
            ->where('role', UserSectionRole::ROLE_ADMIN)
            ->exists();

        $allowedSections = $hasGlobalAdmin
            ? UserSectionRole::ALL_SECTIONS
            : $user->sectionRoles()
            ->pluck('section')
            ->unique()
            ->values()
            ->all();

        if ($allowedSections === []) {
            $allowedSections = [$default];
        }

        $resolved = in_array($requested, $allowedSections, true) ? $requested : $allowedSections[0];
        $request->session()->put('active_section', $resolved);
        app()->instance('currentSection', $resolved);

        return $next($request);
    }
}
