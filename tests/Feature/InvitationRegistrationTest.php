<?php

use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserSectionRole;
use Illuminate\Support\Str;

test('guest can view invitation registration page', function () {
    $inviter = User::factory()->create();
    $token = Str::random(64);

    UserInvitation::query()->create([
        'invited_by_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'token' => $token,
        'expires_at' => now()->addDay(),
    ]);

    $this->get(route('invitations.accept', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/AcceptInvitation')
            ->where('invitation.email', 'invited@example.com'));
});

test('logged in admin is redirected away from invitation page', function () {
    $admin = User::factory()->create();
    UserSectionRole::query()->create([
        'user_id' => $admin->id,
        'section' => UserSectionRole::SECTION_ALL,
        'role' => UserSectionRole::ROLE_ADMIN,
    ]);

    $token = Str::random(64);
    UserInvitation::query()->create([
        'invited_by_user_id' => $admin->id,
        'email' => 'invited@example.com',
        'token' => $token,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('invitations.accept', $token))
        ->assertRedirect(route('dashboard'));
});

test('logged in user without roles is redirected away from invitation page', function () {
    $user = User::factory()->create();
    $token = Str::random(64);

    UserInvitation::query()->create([
        'invited_by_user_id' => $user->id,
        'email' => 'invited@example.com',
        'token' => $token,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('invitations.accept', $token))
        ->assertRedirect(route('dashboard'));
});

test('expired invitation returns not found', function () {
    $inviter = User::factory()->create();
    $token = Str::random(64);

    UserInvitation::query()->create([
        'invited_by_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'token' => $token,
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('invitations.accept', $token))
        ->assertNotFound();
});
