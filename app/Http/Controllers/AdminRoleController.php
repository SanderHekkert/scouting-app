<?php

namespace App\Http\Controllers;

use App\Models\SectionRoleVisibility;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminRoleController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (User $user): array {
                $roles = $user->sectionRoles()->get(['section', 'role']);

                $sectionRole = [];
                foreach (UserSectionRole::ALL_SECTIONS as $section) {
                    $match = $roles->firstWhere('section', $section);
                    $fallback = SectionRoleVisibility::enabledRolesForSection($section)[0] ?? UserSectionRole::ROLE_LEIDING;
                    $sectionRole[$section] = $match?->role ?? $fallback;
                }

                $isAdmin = $roles->contains(fn (UserSectionRole $r) => $r->section === UserSectionRole::SECTION_ALL && $r->role === UserSectionRole::ROLE_ADMIN);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified_at !== null,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'section_roles' => $sectionRole,
                    'selected_section' => collect(UserSectionRole::ALL_SECTIONS)
                        ->first(fn (string $section) => $roles->contains(fn (UserSectionRole $r) => $r->section === $section))
                        ?? UserSectionRole::SECTION_DOLFIJNEN,
                    'is_admin' => $isAdmin,
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
            'is_admin' => ['required', 'boolean'],
            'selected_section' => ['required', 'string', Rule::in(UserSectionRole::ALL_SECTIONS)],
            'selected_role' => ['required', 'string'],
        ]);

        abort_unless(
            in_array((string) $data['selected_role'], SectionRoleVisibility::enabledRolesForSection((string) $data['selected_section']), true),
            422,
            'Deze rol is uitgeschakeld voor deze speltak.'
        );

        DB::transaction(function () use ($user, $data): void {
            UserSectionRole::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'section' => $data['selected_section'],
                ],
                [
                    'role' => $data['selected_role'],
                ],
            );

            UserSectionRole::query()
                ->where('user_id', $user->id)
                ->where('section', UserSectionRole::SECTION_ALL)
                ->delete();
            if ($data['is_admin']) {
                UserSectionRole::query()->create([
                    'user_id' => $user->id,
                    'section' => UserSectionRole::SECTION_ALL,
                    'role' => UserSectionRole::ROLE_ADMIN,
                ]);
            }
        });

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
