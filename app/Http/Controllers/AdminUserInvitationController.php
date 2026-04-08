<?php

namespace App\Http\Controllers;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminUserInvitationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $data['email']));
        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return back()->withErrors(['email' => 'Voor dit e-mailadres bestaat al een gebruiker.']);
        }

        $invitation = UserInvitation::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->latest('id')
            ->first();

        if (! $invitation) {
            $invitation = new UserInvitation;
        }

        $invitation->fill([
            'invited_by_user_id' => (int) $request->user()->id,
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);
        $invitation->save();

        Mail::to($email)->send(new UserInvitationMail($invitation));

        return back()->with('status', 'Uitnodiging verstuurd.');
    }
}
