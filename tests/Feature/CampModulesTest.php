<?php

namespace Tests\Feature;

use App\Models\CampBudget;
use App\Models\CampPlaybook;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampModulesTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $section, string $role): User
    {
        $user = User::factory()->create();
        UserSectionRole::query()->create([
            'user_id' => $user->id,
            'section' => $section,
            'role' => $role,
        ]);

        return $user;
    }

    public function test_admin_can_create_and_update_camp_budget_in_active_section(): void
    {
        $admin = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('camp-budgets.store'), [
                'camp_year' => 2026,
                'title' => 'Pinksterkamp begroting',
                'content' => 'Inkomsten en uitgaven.',
            ])
            ->assertRedirect(route('camp-budgets.index'));

        $budget = CampBudget::query()->firstOrFail();
        $this->assertSame(UserSectionRole::SECTION_DOLFIJNEN, $budget->section);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('camp-budgets.update', $budget), [
                'camp_year' => 2026,
                'title' => 'Pinksterkamp begroting v2',
                'content' => 'Bijgewerkte begroting.',
            ])
            ->assertRedirect(route('camp-budgets.index'));

        $this->assertDatabaseHas('camp_budgets', [
            'id' => $budget->id,
            'title' => 'Pinksterkamp begroting v2',
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
        ]);
    }

    public function test_admin_can_create_and_update_camp_playbook_in_active_section(): void
    {
        $admin = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->post(route('camp-playbooks.store'), [
                'camp_year' => 2026,
                'title' => 'Pinksterkamp draaiboek',
                'content' => 'Planning en taken.',
            ])
            ->assertRedirect(route('camp-playbooks.index'));

        $playbook = CampPlaybook::query()->firstOrFail();
        $this->assertSame(UserSectionRole::SECTION_DOLFIJNEN, $playbook->section);

        $this->actingAs($admin)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('camp-playbooks.update', $playbook), [
                'camp_year' => 2026,
                'title' => 'Pinksterkamp draaiboek v2',
                'content' => 'Bijgewerkte planning.',
            ])
            ->assertRedirect(route('camp-playbooks.index'));

        $this->assertDatabaseHas('camp_playbooks', [
            'id' => $playbook->id,
            'title' => 'Pinksterkamp draaiboek v2',
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
        ]);
    }
}
