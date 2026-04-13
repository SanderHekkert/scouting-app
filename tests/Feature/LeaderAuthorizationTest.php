<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderAuthorizationTest extends TestCase
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

    public function test_admin_cannot_update_leader_from_other_section_via_leaders_route(): void
    {
        $actor = $this->userWithSectionRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $otherSectionLeader = User::factory()->create([
            'first_name' => 'Andere',
            'last_name' => 'Leiding',
        ]);
        UserSectionRole::query()->create([
            'user_id' => $otherSectionLeader->id,
            'section' => UserSectionRole::SECTION_BEVERS,
            'role' => UserSectionRole::ROLE_LEIDING,
        ]);

        $this->actingAs($actor)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('leaders.update', $otherSectionLeader), [
                'first_name' => 'Gehackt',
                'last_name' => 'Naam',
                'installed' => false,
                'gedoopt' => false,
            ])
            ->assertNotFound();

        $this->assertSame('Andere', $otherSectionLeader->fresh()->first_name);
    }

    public function test_admin_cannot_delete_global_admin_via_leaders_route(): void
    {
        $actor = $this->userWithSectionRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $admin = User::factory()->create([
            'first_name' => 'Global',
            'last_name' => 'Admin',
        ]);
        UserSectionRole::query()->create([
            'user_id' => $admin->id,
            'section' => UserSectionRole::SECTION_ALL,
            'role' => UserSectionRole::ROLE_ADMIN,
        ]);

        $this->actingAs($actor)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->delete(route('leaders.destroy', $admin))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
