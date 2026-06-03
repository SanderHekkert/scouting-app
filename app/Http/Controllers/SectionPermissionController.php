<?php

namespace App\Http\Controllers;

use App\Models\SectionPermission;
use App\Models\SectionRoleVisibility;
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
            ->whereIn('role', $editableRoles, 'and', false)
            ->whereIn('module', $allowedModules, 'and', false)
            ->orderBy('role', 'asc')
            ->orderBy('module', 'asc')
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
            'roleVisibility' => SectionRoleVisibility::visibilityMapForSection($section),
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

    public function updateRoleVisibility(Request $request)
    {
        [, $manageableSections, $isAdmin] = $this->accessContext($request);

        $data = $request->validate([
            'section' => ['required', 'string', 'in:'.implode(',', UserSectionRole::ALL_SECTIONS)],
            'roles' => ['required', 'array'],
        ]);
        $section = (string) $data['section'];
        if (! $isAdmin && ! in_array($section, $manageableSections, true)) {
            abort(403, 'Je mag alleen rollen in je eigen speltak beheren.');
        }

        $allowedRoles = SectionRoleVisibility::defaultsForSection($section);
        $roles = collect((array) $data['roles'])
            ->filter(fn ($_, $key): bool => is_string($key) && in_array($key, $allowedRoles, true))
            ->map(fn ($v): bool => (bool) $v)
            ->all();

        abort_unless(collect($allowedRoles)->contains(fn (string $role): bool => (bool) ($roles[$role] ?? true)), 422, 'Minimaal 1 rol moet zichtbaar blijven.');

        foreach ($allowedRoles as $role) {
            SectionRoleVisibility::query()->updateOrCreate(
                ['section' => $section, 'role' => $role],
                ['is_enabled' => (bool) ($roles[$role] ?? true)],
            );
        }

        $disabledRoles = collect($allowedRoles)
            ->filter(fn (string $role): bool => ! ((bool) ($roles[$role] ?? true)))
            ->values()
            ->all();
        if ($disabledRoles !== []) {
            UserSectionRole::query()
                ->where('section', $section)
                ->whereIn('role', $disabledRoles, 'and', false)
                ->delete();
        }

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
            ->whereIn('section', UserSectionRole::ALL_SECTIONS, 'and', false)
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
        return SectionRoleVisibility::enabledRolesForSection($section);
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
            UserSectionRole::ROLE_PENNINGMEESTER => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
            UserSectionRole::ROLE_SECRETARESSE => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
            UserSectionRole::ROLE_VOORZITTER => [
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
                $moduleActions = $this->actionsForModule($section, $role, $module, $actions);
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
            SectionPermission::MODULE_FINANCE,
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
    private function actionsForModule(string $section, string $role, string $module, array $actions): array
    {
        if ($module === SectionPermission::MODULE_FINANCE) {
            if ($section === UserSectionRole::SECTION_BESTUUR && in_array($role, UserSectionRole::BESTUUR_ROLES, true)) {
                return [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ];
            }

            return [
                'can_view' => true,
                'can_create' => true,
                'can_update' => false,
                'can_delete' => false,
            ];
        }

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
