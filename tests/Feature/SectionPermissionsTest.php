<?php

namespace Tests\Feature;

use App\Models\SectionPermission;
use App\Models\User;
use App\Models\UserSectionRole;
use Database\Seeders\SectionPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithSectionRole(string $section, string $role): User
    {
        $user = User::factory()->create();
        UserSectionRole::query()->create([
            'user_id' => $user->id,
            'section' => $section,
            'role' => $role,
        ]);

        return $user;
    }

    public function test_admin_can_pass_module_permission_middleware(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        UserSectionRole::query()->create([
            'user_id' => $admin->id,
            'section' => UserSectionRole::SECTION_ALL,
            'role' => UserSectionRole::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_teamleider_can_manage_permissions_in_own_section(): void
    {
        $this->seed(SectionPermissionsSeeder::class);

        $teamleader = $this->userWithSectionRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_TEAMLEIDER);
        $permission = SectionPermission::query()
            ->where('section', UserSectionRole::SECTION_DOLFIJNEN)
            ->where('role', UserSectionRole::ROLE_LEIDING)
            ->where('module', SectionPermission::MODULE_EVENTS)
            ->firstOrFail();

        $this->actingAs($teamleader)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('permissions.update', $permission), [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ])
            ->assertRedirect();

        $this->assertFalse((bool) $permission->fresh()->can_create);
    }

    public function test_teamleider_is_blocked_from_other_section_permissions(): void
    {
        $this->seed(SectionPermissionsSeeder::class);

        $teamleader = $this->userWithSectionRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_TEAMLEIDER);
        $otherSectionPermission = SectionPermission::query()
            ->where('section', UserSectionRole::SECTION_BEVERS)
            ->where('role', UserSectionRole::ROLE_LEIDING)
            ->where('module', SectionPermission::MODULE_EVENTS)
            ->firstOrFail();

        $this->actingAs($teamleader)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('permissions.update', $otherSectionPermission), [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ])
            ->assertForbidden();
    }

    public function test_create_permission_is_enforced_for_leiding(): void
    {
        $this->seed(SectionPermissionsSeeder::class);

        $user = $this->userWithSectionRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);
        SectionPermission::query()
            ->where('section', UserSectionRole::SECTION_DOLFIJNEN)
            ->where('role', UserSectionRole::ROLE_LEIDING)
            ->where('module', SectionPermission::MODULE_TASK_ITEMS)
            ->update([
                'can_view' => true,
                'can_create' => false,
                'can_update' => true,
                'can_delete' => true,
            ]);

        $this->actingAs($user)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('task-items.store'), [
                'category' => 'Algemeen',
                'title' => 'Test taak',
                'description' => 'Omschrijving',
                'deadlines' => ['2026-05-01'],
            ])
            ->assertForbidden();
    }

    public function test_bestuurslid_can_view_but_not_modify(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        UserSectionRole::query()->create([
            'user_id' => $user->id,
            'section' => UserSectionRole::SECTION_ALL,
            'role' => UserSectionRole::ROLE_BESTUURSLID,
        ]);

        $this->actingAs($user)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('task-items.store'), [
                'category' => 'Algemeen',
                'title' => 'Test taak',
                'description' => 'Omschrijving',
                'deadlines' => ['2026-05-01'],
            ])
            ->assertForbidden();
    }
}
