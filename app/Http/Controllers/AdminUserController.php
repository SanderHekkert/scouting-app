<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSectionRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $allUsers = User::query()
            ->with('sectionRoles:id,user_id,section,role')
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $roles = [];
                foreach ($user->sectionRoles as $row) {
                    $roles[$row->section] = $row->role;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'section_roles' => $roles,
                    'created_at' => optional($user->created_at)->toIso8601String(),
                ];
            })
            ->values();

        $cutoff = Carbon::now()->subDays(14);
        $newUsers = $allUsers
            ->filter(function (array $user) use ($cutoff): bool {
                $createdAt = isset($user['created_at']) ? Carbon::parse($user['created_at']) : null;

                return $createdAt !== null && $createdAt->greaterThanOrEqualTo($cutoff);
            })
            ->values()
            ->all();

        $existingUsers = $allUsers
            ->reject(function (array $user) use ($cutoff): bool {
                $createdAt = isset($user['created_at']) ? Carbon::parse($user['created_at']) : null;

                return $createdAt !== null && $createdAt->greaterThanOrEqualTo($cutoff);
            })
            ->values()
            ->all();

        return Inertia::render('Admin/Users', [
            'users' => $existingUsers,
            'newUsers' => $newUsers,
        ]);
    }

    public function show(User $user)
    {
        $roles = [];
        foreach ($user->sectionRoles()->get(['section', 'role']) as $row) {
            $roles[] = [
                'section' => $row->section,
                'role' => $row->role,
            ];
        }

        return Inertia::render('Admin/UsersShow', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $roles,
            ],
            'sections' => array_merge([UserSectionRole::SECTION_ALL], UserSectionRole::ALL_SECTIONS),
            'localRoles' => [
                UserSectionRole::ROLE_TEAMLEIDER,
                UserSectionRole::ROLE_LEIDING,
                UserSectionRole::ROLE_OUDERCONTACT,
                UserSectionRole::ROLE_LID,
            ],
            'globalRoles' => [
                UserSectionRole::ROLE_ADMIN,
                UserSectionRole::ROLE_BESTUURSLID,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'roles' => ['array'],
            'roles.*.section' => ['required', 'string', Rule::in(array_merge([UserSectionRole::SECTION_ALL], UserSectionRole::ALL_SECTIONS))],
            'roles.*.role' => ['required', 'string', Rule::in(UserSectionRole::ALL_ROLES)],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
        ]);

        $incomingRoles = collect($data['roles'] ?? [])
            ->map(fn (array $row): array => [
                'section' => (string) ($row['section'] ?? ''),
                'role' => (string) ($row['role'] ?? ''),
            ])
            ->filter(fn (array $row): bool => $row['section'] !== '' && $row['role'] !== '')
            ->unique(fn (array $row): string => $row['section'].'|'.$row['role'])
            ->values();

        foreach ($incomingRoles as $row) {
            $this->assertRoleAllowedForSection($row['section'], $row['role']);
        }

        $allowedSections = $incomingRoles->pluck('section')->unique()->all();
        if ($allowedSections === []) {
            $user->sectionRoles()->delete();
        } else {
            $user->sectionRoles()
                ->whereNotIn('section', $allowedSections)
                ->delete();
        }

        foreach ($incomingRoles as $row) {
            $user->sectionRoles()->updateOrCreate(
                ['section' => $row['section']],
                ['role' => $row['role']],
            );
        }

        return back();
    }

    public function destroy(Request $request, User $user)
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->withErrors(['user' => 'Je kunt je eigen account niet verwijderen.']);
        }

        $user->sectionRoles()->delete();
        $user->delete();

        return back();
    }

    private function assertRoleAllowedForSection(string $section, ?string $role): void
    {
        if ($role === null || $role === '') {
            return;
        }

        if ($section === UserSectionRole::SECTION_ALL) {
            if (! in_array($role, [UserSectionRole::ROLE_ADMIN, UserSectionRole::ROLE_BESTUURSLID], true)) {
                abort(422, 'Alleen admin of bestuurslid is toegestaan voor Globaal.');
            }

            return;
        }

        if (in_array($role, [UserSectionRole::ROLE_ADMIN, UserSectionRole::ROLE_BESTUURSLID], true)) {
            abort(422, 'Admin en bestuurslid zijn alleen toegestaan als globale rol.');
        }
    }
}
