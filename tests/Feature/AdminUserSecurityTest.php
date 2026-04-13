<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function assignRole(User $user, string $section, string $role): void
    {
        UserSectionRole::query()->create([
            'user_id' => $user->id,
            'section' => $section,
            'role' => $role,
        ]);
    }

    public function test_bestuurslid_cannot_promote_user_to_global_admin(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create();
        /** @var User $target */
        $target = User::factory()->create();

        $this->assignRole($actor, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $this->assignRole($target, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);

        $this->actingAs($actor)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_ALL, 'role' => UserSectionRole::ROLE_ADMIN],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_ALL,
            'role' => UserSectionRole::ROLE_ADMIN,
        ]);
    }

    public function test_bestuurslid_cannot_delete_global_admin_account(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create();
        /** @var User $target */
        $target = User::factory()->create();

        $this->assignRole($actor, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $this->assignRole($target, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($actor)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
