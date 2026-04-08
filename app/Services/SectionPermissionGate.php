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

        if ($user->isGlobalAdmin()) {
            return true;
        }
        if ($user->isGlobalBoardMember()) {
            return $action === 'view';
        }

        $role = $user->roleInSection($section);
        if ($role === null) {
            return false;
        }

        if ($role === UserSectionRole::ROLE_TEAMLEIDER) {
            return true;
        }

        if ($role === UserSectionRole::ROLE_LID) {
            return $module === SectionPermission::MODULE_EVENTS && $action === 'view';
        }

        if (! in_array($role, [UserSectionRole::ROLE_LEIDING, UserSectionRole::ROLE_OUDERCONTACT], true)) {
            return false;
        }

        $permission = SectionPermission::query()
            ->where('section', $section)
            ->where('role', $role)
            ->where('module', $module)
            ->first();

        if (! $permission) {
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
