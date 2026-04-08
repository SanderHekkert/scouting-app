<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationRegistrationController extends Controller
{
    public function create(string $token): Response
    {
        $invitation = $this->resolveValidInvitation($token);

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => [
                'email' => $invitation->email,
                'token' => $invitation->token,
                'expires_at' => optional($invitation->expires_at)->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = $this->resolveValidInvitation($token);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'birthday' => ['nullable', 'date'],
            'phone_number' => ['nullable', 'string', 'max:40'],
            'bijzonderheden' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (User::query()->where('email', $invitation->email)->exists()) {
            return to_route('login')->with('status', 'Account bestaat al. Log in of reset je wachtwoord.');
        }

        $fullName = trim($data['first_name'].' '.$data['last_name']);
        $user = User::query()->create([
            'name' => $fullName !== '' ? $fullName : $data['first_name'],
            'email' => $invitation->email,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address' => $data['address'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'bijzonderheden' => $data['bijzonderheden'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $invitation->accepted_at = now();
        $invitation->save();

        $user->sendEmailVerificationNotification();

        return to_route('login')->with(
            'status',
            'Je account is aangemaakt. Check je mail en verifieer je e-mailadres voordat je inlogt.'
        );
    }

    private function resolveValidInvitation(string $token): UserInvitation
    {
        $invitation = UserInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation || ! $invitation->expires_at || $invitation->expires_at->isPast()) {
            abort(404, 'Uitnodiging ongeldig of verlopen.');
        }

        return $invitation;
    }
}
