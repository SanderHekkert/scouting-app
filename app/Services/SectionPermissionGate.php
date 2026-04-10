<?php

namespace App\Services;

use App\Models\SectionPermission;
use App\Models\User;
use App\Models\UserSectionRole;

class SectionPermissionGate
{
    public function allows(?User $user, string $section, string $module, string $action): bool
    {
        if (! $user) {
            return false;
        }

        // Profiel is altijd toegestaan voor elk ingelogd account.
        if ($module === SectionPermission::MODULE_PROFILE) {
            return true;
        }

        if ($user->isGlobalAdmin()) {
            return true;
        }
        if ($user->isGlobalBoardMember()) {
            if ($action === 'view') {
                return true;
            }

            if (in_array($module, [SectionPermission::MODULE_CAMP_BUDGETS, SectionPermission::MODULE_CAMP_PLAYBOOKS], true)) {
                return true;
            }

            return $module === SectionPermission::MODULE_INFO_NOTES
                && $action === 'create'
                && $section === UserSectionRole::SECTION_BESTUUR
                || ($module === SectionPermission::MODULE_TASK_ITEMS
                    && $action === 'create'
                    && $section === UserSectionRole::SECTION_BESTUUR);
        }

        $role = $user->roleInSection($section);
        if ($role === null) {
            return false;
        }

        if ($role === UserSectionRole::ROLE_TEAMLEIDER) {
            return true;
        }

        $permission = SectionPermission::query()
            ->where('section', $section)
            ->where('role', $role)
            ->where('module', $module)
            ->first();

        if (! $permission) {
            if (in_array($module, [SectionPermission::MODULE_CAMP_BUDGETS, SectionPermission::MODULE_CAMP_PLAYBOOKS], true)) {
                return in_array($action, ['view', 'create', 'update'], true);
            }

            if ($module === SectionPermission::MODULE_FINANCE) {
                if ($section === UserSectionRole::SECTION_BESTUUR && in_array($role, UserSectionRole::BESTUUR_ROLES, true)) {
                    return true;
                }

                return in_array($action, ['view', 'create'], true);
            }

            if ($role === UserSectionRole::ROLE_LID) {
                return $module === SectionPermission::MODULE_EVENTS && $action === 'view';
            }

            return false;
        }

        return match ($action) {
            'view' => (bool) $permission->can_view,
            'create' => (bool) $permission->can_create,
            'update' => (bool) $permission->can_update,
            'delete' => (bool) $permission->can_delete,
            default => false,
        };
    }
}
