<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
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

    public function test_admin_can_assign_local_role_to_user(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_ZEEVERKENNERS, 'role' => UserSectionRole::ROLE_LEIDING],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_ZEEVERKENNERS,
            'role' => UserSectionRole::ROLE_LEIDING,
        ]);
    }

    public function test_invalid_global_role_returns_validation_error_instead_of_html_error_page(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_ALL, 'role' => UserSectionRole::ROLE_LEIDING],
                ],
            ])
            ->assertSessionHasErrors(['roles']);
    }

    public function test_bestuur_role_on_local_section_returns_validation_error(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_DOLFIJNEN, 'role' => UserSectionRole::ROLE_ADMIN],
                ],
            ])
            ->assertSessionHasErrors(['roles']);
    }

    public function test_admin_can_assign_bestuur_section_role_to_user(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_BESTUUR, 'role' => UserSectionRole::ROLE_PENNINGMEESTER],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_BESTUUR,
            'role' => UserSectionRole::ROLE_PENNINGMEESTER,
        ]);
    }

    public function test_admin_can_add_second_section_role_to_existing_user(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);
        $this->assignRole($target, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_DOLFIJNEN, 'role' => UserSectionRole::ROLE_LEIDING],
                    ['section' => UserSectionRole::SECTION_BEVERS, 'role' => UserSectionRole::ROLE_LEIDING],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_BEVERS,
            'role' => UserSectionRole::ROLE_LEIDING,
        ]);
    }
}
