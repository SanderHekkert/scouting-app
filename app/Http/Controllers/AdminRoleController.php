<?php

namespace App\Http\Controllers;

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
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $roles = $user->sectionRoles()->get(['section', 'role']);

                $sectionRole = [];
                $sectionEnabled = [];
                foreach (UserSectionRole::ALL_SECTIONS as $section) {
                    $match = $roles->firstWhere('section', $section);
                    $sectionEnabled[$section] = $match !== null;
                    $sectionRole[$section] = $match?->role ?? UserSectionRole::ROLE_LEIDING;
                }

                $isAdmin = $roles->contains(fn (UserSectionRole $r) => $r->section === UserSectionRole::SECTION_ALL && $r->role === UserSectionRole::ROLE_ADMIN);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'section_enabled' => $sectionEnabled,
                    'section_roles' => $sectionRole,
                    'selected_section' => collect(UserSectionRole::ALL_SECTIONS)
                        ->first(fn (string $section) => $sectionEnabled[$section] ?? false)
                        ?? UserSectionRole::SECTION_DOLFIJNEN,
                    'is_admin' => $isAdmin,
                ];
            })
            ->values();

        return Inertia::render('Admin/Roles', [
            'users' => $users,
            'sections' => UserSectionRole::ALL_SECTIONS,
            'roles' => [
                UserSectionRole::ROLE_TEAMLEIDER,
                UserSectionRole::ROLE_LEIDING,
                UserSectionRole::ROLE_OUDERCONTACT,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'is_admin' => ['required', 'boolean'],
            'selected_section' => ['required', 'string', Rule::in(UserSectionRole::ALL_SECTIONS)],
            'selected_role' => ['required', 'string', 'in:teamleider,leiding,ouder_contact'],
        ]);

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
}
