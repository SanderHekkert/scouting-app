<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectAfterSaveTest extends TestCase
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

    public function test_user_update_redirects_to_previous_page_when_requested(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->withSession(['inertia_return_url' => route('admin.users.index')])
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [],
                'redirect_back' => true,
            ])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_user_update_stays_on_page_without_redirect_flag(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [],
            ])
            ->assertRedirect(route('admin.users.show', $target));
    }

    public function test_agenda_update_redirects_back_after_save(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $item = \App\Models\AgendaItem::query()->create([
            'owner_user_id' => $user->id,
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'theme' => 'Test activiteit',
            'event_date' => '2026-06-22',
            'end_date' => '2026-06-22',
            'audience_scope' => 'self',
            'target_user_ids' => [],
        ]);

        $returnUrl = route('agenda.index');

        $response = $this->actingAs($user)
            ->withSession([
                'active_section' => UserSectionRole::SECTION_DOLFIJNEN,
                'inertia_return_url' => $returnUrl,
            ])
            ->from(route('agenda.edit', $item))
            ->patch(route('agenda.update', $item), [
                'theme' => 'Bijgewerkt',
                'event_date' => '2026-06-22',
                'end_date' => '2026-06-22',
                'redirect_back' => true,
                'return_url' => $returnUrl,
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Bijgewerkt', $item->fresh()->theme);
        $response->assertRedirect($returnUrl);
    }
}
