<?php

namespace Database\Seeders;

use App\Models\SectionPermission;
use App\Models\UserSectionRole;
use Illuminate\Database\Seeder;

class SectionPermissionsSeeder extends Seeder
{
    public function run(): void
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

        foreach (UserSectionRole::ALL_SECTIONS as $section) {
            $modulesForSection = $this->allowedModulesForSection($section);
            $rolesForSection = match ($section) {
                UserSectionRole::SECTION_BESTUUR => [
                    UserSectionRole::ROLE_BESTUURSLID,
                ],
                UserSectionRole::SECTION_WILDE_VAART, UserSectionRole::SECTION_LOODSEN => [
                    UserSectionRole::ROLE_LID,
                    UserSectionRole::ROLE_LEIDING,
                    UserSectionRole::ROLE_TEAMLEIDER,
                ],
                default => [
                    UserSectionRole::ROLE_LEIDING,
                    UserSectionRole::ROLE_OUDERCONTACT,
                    UserSectionRole::ROLE_TEAMLEIDER,
                ],
            };

            foreach ($rolesForSection as $role) {
                $actions = $defaults[$role] ?? [
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];
                foreach ($modulesForSection as $module) {
                    $moduleActions = $this->actionsForModule($section, $role, $module, $actions);
                    SectionPermission::query()->updateOrCreate(
                        [
                            'section' => $section,
                            'role' => $role,
                            'module' => $module,
                        ],
                        $moduleActions
                    );
                }
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
            SectionPermission::MODULE_PROFILE,
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
            if ($section === UserSectionRole::SECTION_BESTUUR && $role === UserSectionRole::ROLE_BESTUURSLID) {
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
