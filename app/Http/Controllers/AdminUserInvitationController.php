<?php

namespace App\Http\Controllers;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminUserInvitationController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/UsersInvite');
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = $data['email'];
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
            'expires_at' => now()->addHours(24),
            'accepted_at' => null,
        ]);
        $invitation->save();

        Mail::to($email)->send(new UserInvitationMail($invitation));

        return $this->redirectAfterSave($request, config('save-redirects.admin_users'))
            ->with('status', 'Uitnodiging verstuurd.');
    }
}
