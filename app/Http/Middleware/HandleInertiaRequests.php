<?php

namespace App\Http\Middleware;

use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $sectionRoles = $user
            ? $user->sectionRoles()->get(['section', 'role'])->map(fn (UserSectionRole $r) => [
                'section' => $r->section,
                'role' => $r->role,
            ])->all()
            : [];
        $activeSection = app()->bound('currentSection')
            ? app('currentSection')
            : UserSectionRole::SECTION_DOLFIJNEN;

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $user,
                'active_section' => $activeSection,
                'section_roles' => $sectionRoles,
            ],
        ];
    }
}
