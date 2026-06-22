<?php

namespace App\Http\Controllers;

use App\Models\SectionRoleVisibility;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminRoleController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('sectionRoles:id,user_id,section,role')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (User $user): array {
                $roles = $user->sectionRoles;
                $sectionRole = [];

                foreach ($roles as $row) {
                    if ($row->section === UserSectionRole::SECTION_ALL) {
                        continue;
                    }

                    $sectionRole[$row->section] = $row->role;
                }

                $selectedSection = collect(UserSectionRole::ALL_SECTIONS)
                    ->first(fn (string $section): bool => array_key_exists($section, $sectionRole))
                    ?? UserSectionRole::SECTION_DOLFIJNEN;

                $availableRoles = SectionRoleVisibility::enabledRolesForSection($selectedSection);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified_at !== null,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'section_roles' => $sectionRole,
                    'selected_section' => $selectedSection,
                    'selected_role' => $sectionRole[$selectedSection]
                        ?? ($availableRoles[0] ?? UserSectionRole::ROLE_LEIDING),
                    'is_admin' => $roles->contains(
                        fn (UserSectionRole $role): bool => $role->section === UserSectionRole::SECTION_ALL
                            && $role->role === UserSectionRole::ROLE_ADMIN
                    ),
                ];
            })
            ->values();

        return Inertia::render('Admin/Roles', [
            'users' => $users,
            'sections' => UserSectionRole::ALL_SECTIONS,
            'rolesBySection' => collect(UserSectionRole::ALL_SECTIONS)
                ->mapWithKeys(fn (string $section): array => [$section => SectionRoleVisibility::enabledRolesForSection($section)])
                ->all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'selected_section' => ['required', 'string', Rule::in(UserSectionRole::ALL_SECTIONS)],
            'selected_role' => ['required', 'string', Rule::in(UserSectionRole::ALL_ROLES)],
        ]);

        $section = (string) $data['selected_section'];
        $role = (string) $data['selected_role'];

        if (! in_array($role, SectionRoleVisibility::enabledRolesForSection($section), true)) {
            throw ValidationException::withMessages([
                'selected_role' => 'Deze rol is uitgeschakeld voor deze speltak.',
            ]);
        }

        if (in_array($role, [UserSectionRole::ROLE_ADMIN, ...UserSectionRole::BESTUUR_ROLES], true)
            && $section !== UserSectionRole::SECTION_BESTUUR) {
            throw ValidationException::withMessages([
                'selected_role' => 'Admin en bestuursrollen zijn alleen toegestaan als globale rol of binnen Bestuur.',
            ]);
        }

        $user->sectionRoles()->updateOrCreate(
            ['section' => $section],
            ['role' => $role],
        );

        return back();
    }

    public function updateBasics(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return back();
    }
}
