<?php

namespace App\Http\Middleware;

use App\Models\SectionPermission;
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
        $activeSection = session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $permissions = [];
        if ($user) {
            $isAdmin = $user->isGlobalAdmin();
            $isBoard = $user->isGlobalBoardMember();
            $role = $user->roleInSection($activeSection);

            if ($isAdmin) {
                foreach (SectionPermission::ALL_MODULES as $module) {
                    $permissions[$module] = [
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ];
                }
            } elseif ($isBoard) {
                foreach (SectionPermission::ALL_MODULES as $module) {
                    $permissions[$module] = [
                        'view' => true,
                        'create' => false,
                        'update' => false,
                        'delete' => false,
                    ];
                }
                if ($activeSection === UserSectionRole::SECTION_BESTUUR) {
                    $permissions[SectionPermission::MODULE_INFO_NOTES]['create'] = true;
                    $permissions[SectionPermission::MODULE_TASK_ITEMS]['create'] = true;
                }
            } elseif ($role === UserSectionRole::ROLE_TEAMLEIDER) {
                foreach (SectionPermission::ALL_MODULES as $module) {
                    $permissions[$module] = [
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ];
                }
            } elseif ($role) {
                foreach (SectionPermission::ALL_MODULES as $module) {
                    $permissions[$module] = [
                        'view' => false,
                        'create' => false,
                        'update' => false,
                        'delete' => false,
                    ];
                }

                $rows = SectionPermission::query()
                    ->where('section', $activeSection)
                    ->where('role', $role)
                    ->get();
                foreach ($rows as $row) {
                    $permissions[$row->module] = [
                        'view' => (bool) $row->can_view,
                        'create' => (bool) $row->can_create,
                        'update' => (bool) $row->can_update,
                        'delete' => (bool) $row->can_delete,
                    ];
                }

                if ($role === UserSectionRole::ROLE_LID && $rows->isEmpty()) {
                    $permissions[SectionPermission::MODULE_EVENTS] = [
                        'view' => true,
                        'create' => false,
                        'update' => false,
                        'delete' => false,
                    ];
                }

                if (! isset($permissions[SectionPermission::MODULE_FINANCE])) {
                    $permissions[SectionPermission::MODULE_FINANCE] = [
                        'view' => true,
                        'create' => true,
                        'update' => false,
                        'delete' => false,
                    ];
                }
            }

            // Profiel is altijd volledig beschikbaar voor elk account.
            $permissions[SectionPermission::MODULE_PROFILE] = [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
            ];
        }

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $user,
                'active_section' => $activeSection,
                'section_roles' => $sectionRoles,
                'permissions' => $permissions,
            ],
        ];
    }
}
