<?php

namespace App\Http\Controllers;

use App\Models\SectionPermission;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionPermissionController extends Controller
{
    public function index(Request $request): Response
    {
        [$user, $manageableSections, $isAdmin] = $this->accessContext($request);

        $requestedSection = (string) $request->query('section', '');
        $section = $isAdmin
            ? (in_array($requestedSection, $manageableSections, true) ? $requestedSection : ($manageableSections[0] ?? UserSectionRole::SECTION_DOLFIJNEN))
            : ($manageableSections[0] ?? UserSectionRole::SECTION_DOLFIJNEN);

        $editableRoles = $this->editableRolesForSection($section);
        $allowedModules = $this->allowedModulesForSection($section);
        $this->ensureRowsForSection($section, $editableRoles, $allowedModules);

        $rows = SectionPermission::query()
            ->where('section', $section)
            ->whereIn('role', $editableRoles)
            ->whereIn('module', $allowedModules)
            ->orderBy('role')
            ->orderBy('module')
            ->get()
            ->map(fn (SectionPermission $row) => [
                'id' => $row->id,
                'section' => $row->section,
                'role' => $row->role,
                'module' => $row->module,
                'can_view' => (bool) $row->can_view,
                'can_create' => (bool) $row->can_create,
                'can_update' => (bool) $row->can_update,
                'can_delete' => (bool) $row->can_delete,
            ])
            ->values();

        return Inertia::render('Admin/Permissions', [
            'manageableSections' => $manageableSections,
            'selectedSection' => $section,
            'isAdmin' => $isAdmin,
            'rows' => $rows,
            'roles' => $editableRoles,
            'modules' => $allowedModules,
        ]);
    }

    public function update(Request $request, SectionPermission $sectionPermission)
    {
        [, $manageableSections, $isAdmin] = $this->accessContext($request);

        if (! $isAdmin && ! in_array($sectionPermission->section, $manageableSections, true)) {
            abort(403, 'Je mag alleen rechten in je eigen speltak beheren.');
        }

        $data = $request->validate([
            'can_view' => ['required', 'boolean'],
            'can_create' => ['required', 'boolean'],
            'can_update' => ['required', 'boolean'],
            'can_delete' => ['required', 'boolean'],
        ]);

        $sectionPermission->update($data);

        return back();
    }

    /**
     * @return array{0:User,1:list<string>,2:bool}
     */
    private function accessContext(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $isAdmin = $user->isGlobalAdmin();
        if ($isAdmin) {
            return [$user, UserSectionRole::ALL_SECTIONS, true];
        }

        $manageableSections = $user->sectionRoles()
            ->where('role', UserSectionRole::ROLE_TEAMLEIDER)
            ->whereIn('section', UserSectionRole::ALL_SECTIONS)
            ->pluck('section')
            ->unique()
            ->values()
            ->all();

        if ($manageableSections === []) {
            abort(403, 'Alleen teamleider of admin kan rechten beheren.');
        }

        return [$user, $manageableSections, false];
    }

    /**
     * @return list<string>
     */
    private function editableRolesForSection(string $section): array
    {
        if ($section === UserSectionRole::SECTION_BESTUUR) {
            return [UserSectionRole::ROLE_BESTUURSLID];
        }

        if (in_array($section, [UserSectionRole::SECTION_WILDE_VAART, UserSectionRole::SECTION_LOODSEN], true)) {
            return [
                UserSectionRole::ROLE_LID,
                UserSectionRole::ROLE_LEIDING,
                UserSectionRole::ROLE_TEAMLEIDER,
            ];
        }

        return [
            UserSectionRole::ROLE_LEIDING,
            UserSectionRole::ROLE_OUDERCONTACT,
            UserSectionRole::ROLE_TEAMLEIDER,
        ];
    }

    /**
     * @param  list<string>  $roles
     */
    private function ensureRowsForSection(string $section, array $roles, array $modules): void
    {
        $defaults = [
            UserSectionRole::ROLE_TEAMLEIDER => [
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ],
            UserSectionRole::ROLE_LEIDING => [
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ],
            UserSectionRole::ROLE_OUDERCONTACT => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
            UserSectionRole::ROLE_LID => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
            UserSectionRole::ROLE_BESTUURSLID => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
        ];

        foreach ($roles as $role) {
            $actions = $defaults[$role] ?? [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ];
            foreach ($modules as $module) {
                $moduleActions = $this->actionsForModule($module, $actions);
                SectionPermission::query()->firstOrCreate(
                    [
                        'section' => $section,
                        'role' => $role,
                        'module' => $module,
                    ],
                    $moduleActions,
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function allowedModulesForSection(string $section): array
    {
        $base = [
            SectionPermission::MODULE_DASHBOARD,
            SectionPermission::MODULE_EVENTS,
            SectionPermission::MODULE_MEMBERS,
            SectionPermission::MODULE_LEADERS,
            SectionPermission::MODULE_INFO_NOTES,
            SectionPermission::MODULE_TASK_ITEMS,
        ];

        if (in_array($section, [UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::SECTION_ZEEVERKENNERS, UserSectionRole::SECTION_BESTUUR], true)) {
            $base[] = SectionPermission::MODULE_PODS;
        }
        if (in_array($section, [UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::SECTION_BESTUUR], true)) {
            $base[] = SectionPermission::MODULE_TIPPER_TOPPER;
        }
        if (in_array($section, [UserSectionRole::SECTION_BEVERS, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::SECTION_BESTUUR], true)) {
            $base[] = SectionPermission::MODULE_YEAR_THEME;
        }

        return array_values(array_unique($base));
    }

    /**
     * @param  array{can_view:bool,can_create:bool,can_update:bool,can_delete:bool}  $actions
     * @return array{can_view:bool,can_create:bool,can_update:bool,can_delete:bool}
     */
    private function actionsForModule(string $module, array $actions): array
    {
        if ($module === SectionPermission::MODULE_PODS) {
            return [
                'can_view' => false,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ];
        }

        return $actions;
    }
}
