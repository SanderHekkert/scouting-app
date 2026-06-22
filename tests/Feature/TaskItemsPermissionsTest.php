<?php

namespace Tests\Feature;

use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use Database\Seeders\SectionPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskItemsPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SectionPermissionsSeeder::class);
    }

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
                'redirect_back' => true,
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
                'redirect_back' => true,
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

    public function test_teamleider_can_mark_task_completed_with_timestamp(): void
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
            'title' => 'Taak',
            'description' => 'Beschrijving',
        ]);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => true,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNotNull($task->completed_at);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => false,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNull($task->completed_at);
    }

    public function test_leiding_can_mark_task_completed(): void
    {
        $leiding = $this->userWithRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);
        TaskCategory::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Algemeen',
            'position' => 1,
        ]);
        $task = TaskItem::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Algemeen',
            'title' => 'Taak',
            'description' => 'Beschrijving',
        ]);

        $this->actingAs($leiding)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => true,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNotNull($task->completed_at);
    }

    public function test_teamleider_can_mark_individual_deadline_completed(): void
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
            'title' => 'Taak met deadlines',
            'description' => 'Beschrijving',
            'deadlines' => ['2026-06-10', '2026-06-20'],
        ]);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => true,
                'deadline' => '2026-06-10',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNull($task->completed_at);
        $this->assertSame(
            ['2026-06-10'],
            array_keys($task->deadline_completions ?? []),
        );
        $firstCompletion = $task->deadline_completions['2026-06-10'] ?? null;
        $this->assertIsArray($firstCompletion);
        $this->assertSame($teamleider->id, $firstCompletion['completed_by_user_id'] ?? null);
        $this->assertNotEmpty($firstCompletion['completed_at'] ?? null);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => true,
                'deadline' => '2026-06-20',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNotNull($task->completed_at);
        $this->assertCount(2, $task->deadline_completions ?? []);
    }

    public function test_lid_cannot_mark_unassigned_task_completed(): void
    {
        $lid = $this->userWithRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LID);
        TaskCategory::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'name' => 'Algemeen',
            'position' => 1,
        ]);
        $task = TaskItem::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Algemeen',
            'title' => 'Taak',
            'description' => 'Beschrijving',
        ]);

        $this->actingAs($lid)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('task-items.complete', $task), [
                'completed' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('task_items', [
            'id' => $task->id,
            'completed_at' => null,
        ]);
    }
}
