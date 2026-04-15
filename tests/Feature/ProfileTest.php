<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function makeUser(): User
{
    $user = User::factory()->createOne();
    assert($user instanceof User);

    return $user;
}

test('profile page is displayed', function () {
    $user = makeUser();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = makeUser();
    Notification::fake();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = makeUser();
    Notification::fake();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
    Notification::assertNothingSent();
});

test('user can log out other browser sessions', function () {
    config()->set('session.driver', 'database');

    $user = makeUser();

    $this->actingAs($user);
    $this->startSession();

    $currentSessionId = session()->getId();
    $otherSessionId = 'other-device-session';

    DB::table((string) config('session.table', 'sessions'))->insert([
        [
            'id' => $currentSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Current Browser',
            'payload' => '{}',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => $otherSessionId,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => 'Other Browser',
            'payload' => '{}',
            'last_activity' => now()->subMinute()->timestamp,
        ],
    ]);

    $response = $this->delete('/profile/other-browser-sessions', [
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId]);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);
});

test('user can delete their account', function () {
    $user = makeUser();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = makeUser();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
