<?php

namespace Tests\Feature;

use App\Models\InfoNote;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoNotesPermissionsTest extends TestCase
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

    public function test_bestuur_can_create_info_note_for_other_section(): void
    {
        $board = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);

        $this->actingAs($board)
            ->withSession(['active_section' => UserSectionRole::SECTION_BESTUUR])
            ->post(route('info-notes.store'), [
                'category' => 'Kamp',
                'content' => 'Informatie voor dolfijnen',
                'link' => 'https://example.com',
                'target_section' => UserSectionRole::SECTION_DOLFIJNEN,
            ])
            ->assertRedirect(route('info-notes.index'));

        $this->assertDatabaseHas('info_notes', [
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Kamp',
        ]);
    }

    public function test_teamleider_can_update_note_for_own_section(): void
    {
        $teamleider = $this->userWithRole(UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_TEAMLEIDER);
        $note = InfoNote::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Oud',
            'content' => 'Oude inhoud',
            'link' => null,
        ]);

        $this->actingAs($teamleider)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('info-notes.update', $note), [
                'category' => 'Nieuw',
                'content' => 'Nieuwe inhoud',
                'link' => 'https://example.org',
            ])
            ->assertRedirect(route('info-notes.index'));

        $this->assertDatabaseHas('info_notes', [
            'id' => $note->id,
            'category' => 'Nieuw',
            'content' => 'Nieuwe inhoud',
        ]);
    }

    public function test_bestuur_cannot_update_note_for_other_section(): void
    {
        $board = $this->userWithRole(UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $note = InfoNote::withoutGlobalScope('section')->create([
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'category' => 'Kamp',
            'content' => 'Origineel',
            'link' => null,
        ]);

        $this->actingAs($board)
            ->withSession(['active_section' => UserSectionRole::SECTION_DOLFIJNEN])
            ->patch(route('info-notes.update', $note), [
                'category' => 'Aangepast',
                'content' => 'Aangepast door bestuur',
                'link' => '',
            ])
            ->assertForbidden();
    }
}
