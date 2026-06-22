<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserSectionRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
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

    public function test_admin_can_invite_new_user_by_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.invite.create'))
            ->post(route('admin.users.invite'), [
                'email' => 'Nieuw.Gebruiker@Example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'nieuw.gebruiker@example.com',
        ]);
    }

    public function test_invite_rejects_existing_user_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $existing = User::factory()->create(['email' => 'bestaat@example.com']);
        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.invite.create'))
            ->post(route('admin.users.invite'), [
                'email' => $existing->email,
            ])
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('user_invitations', 0);
    }

    public function test_admin_can_update_user_profile_fields(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create([
            'name' => 'Oud',
            'email' => 'oud@example.com',
            'first_name' => 'Oud',
            'last_name' => 'Naam',
        ]);
        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);
        $this->assignRole($target, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => 'Nieuw',
                'email' => 'nieuw@example.com',
                'first_name' => 'Nieuw',
                'last_name' => 'Naam',
                'roles' => [
                    ['section' => UserSectionRole::SECTION_DOLFIJNEN, 'role' => UserSectionRole::ROLE_LEIDING],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Nieuw',
            'email' => 'nieuw@example.com',
            'first_name' => 'Nieuw',
            'last_name' => 'Naam',
        ]);
    }

    public function test_admin_can_remove_all_roles_from_user(): void
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
                'roles' => [],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('user_section_roles', [
            'user_id' => $target->id,
        ]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors(['user']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_bestuurslid_cannot_delete_global_admin_with_error_message(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $this->assignRole($actor, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $this->assignRole($target, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);

        $this->actingAs($actor)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect()
            ->assertSessionHasErrors(['user']);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_bestuurslid_can_add_local_role_to_user_with_unchanged_global_role(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $this->assignRole($actor, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $this->assignRole($target, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_BESTUURSLID);
        $this->assignRole($target, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);

        $this->actingAs($actor)
            ->from(route('admin.users.show', $target))
            ->patch(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [
                    ['section' => UserSectionRole::SECTION_ALL, 'role' => UserSectionRole::ROLE_BESTUURSLID],
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

    public function test_admin_roles_update_assigns_section_role_without_removing_global_admin(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $this->assignRole($admin, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);
        $this->assignRole($target, UserSectionRole::SECTION_ALL, UserSectionRole::ROLE_ADMIN);
        $this->assignRole($target, UserSectionRole::SECTION_DOLFIJNEN, UserSectionRole::ROLE_LEIDING);

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->patch(route('admin.roles.update', $target), [
                'selected_section' => UserSectionRole::SECTION_DOLFIJNEN,
                'selected_role' => UserSectionRole::ROLE_TEAMLEIDER,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_ALL,
            'role' => UserSectionRole::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('user_section_roles', [
            'user_id' => $target->id,
            'section' => UserSectionRole::SECTION_DOLFIJNEN,
            'role' => UserSectionRole::ROLE_TEAMLEIDER,
        ]);
    }

    public function test_invitation_registration_creates_user_without_roles(): void
    {
        $inviter = User::factory()->create();
        $invitation = UserInvitation::query()->create([
            'invited_by_user_id' => $inviter->id,
            'email' => 'nieuw@example.com',
            'token' => 'test-token-123',
            'expires_at' => now()->addDay(),
        ]);

        $this->post(route('invitations.complete', $invitation->token), [
            'first_name' => 'Nieuw',
            'last_name' => 'Gebruiker',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('login'));

        $user = User::query()->where('email', 'nieuw@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Nieuw Gebruiker', $user->name);
        $this->assertDatabaseMissing('user_section_roles', ['user_id' => $user->id]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }
}
