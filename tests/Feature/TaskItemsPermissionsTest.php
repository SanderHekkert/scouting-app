<?php

namespace Tests\Feature;

use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskItemsPermissionsTest extends TestCase
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

    public function test_bestuur_can_create_task_for_other_section(): void
    {
        $board = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        TaskCategory::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Algemeen',
            'position' => 1,
        ]);

        $this->actingAs($board)
            ->withSession(['active_section' => UserSectionRole::SECTION_BESTUUR])
            ->post(route('task-items.store'), [
                'target_section' => UserSectionRole::SECTION_DOLFIJNEN,
                'category' => 'Algemeen',
                'title' => 'Voorraad tellen',
                'description' => 'Check materiaal en voorraad.',
                'owner_user_ids' => [],
                'deadlines' => [],
                'shared_sections' => [],
            ])
            ->assertRedirect(route('task-items.index'));

        $this->assertDatabaseHas('task_items', [
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'title' => 'Voorraad tellen',
        ]);
    }

    public function test_teamleider_can_update_task_for_own_section(): void
    {
        $teamleider = $this->userWithRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_TEAMLEIDER);
        TaskCategory::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Algemeen',
            'position' => 1,
        ]);
        $task = TaskItem::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Algemeen',
            'title' => 'Oud',
            'description' => 'Oude tekst',
        ]);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.update', $task), [
                'category' => 'Algemeen',
                'title' => 'Nieuw',
                'description' => 'Nieuwe tekst',
                'owner_user_ids' => [],
                'deadlines' => [],
                'shared_sections' => [],
            ])
            ->assertRedirect(route('task-items.index'));

        $this->assertDatabaseHas('task_items', [
            'id' => $task->id,
            'title' => 'Nieuw',
            'description' => 'Nieuwe tekst',
        ]);
    }

    public function test_bestuur_cannot_update_task_for_other_section(): void
    {
        $board = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $task = TaskItem::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Algemeen',
            'title' => 'Blijft staan',
            'description' => 'Origineel',
        ]);
        TaskCategory::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Algemeen',
            'position' => 1,
        ]);

        $this->actingAs($board)
            ->withSession(['active_section' => UserSectionRole::SECTION_BESTUUR])
            ->patch(route('task-items.update', $task), [
                'category' => 'Algemeen',
                'title' => 'Aangepast',
                'description' => 'Aangepast',
                'owner_user_ids' => [],
                'deadlines' => [],
                'shared_sections' => [],
            ])
            ->assertForbidden();
    }
}
