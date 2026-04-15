<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'browserSessions' => $this->browserSessions($request),
            'push' => [
                'vapidPublicKey' => (string) config('services.webpush.vapid_public_key'),
                'isSubscribed' => $user->pushSubscriptions()->exists(),
            ],
            'currentTheme' => (string) ($user->theme_preference ?: 'light'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $emailWasChanged = $user->isDirty('email');

        if ($emailWasChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailWasChanged && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();

            return Redirect::route('profile.edit')->with('status', 'verification-link-sent');
        }

        return Redirect::route('profile.edit');
    }

    public function destroyOtherBrowserSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices((string) $request->input('password'));

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return Redirect::route('profile.edit')->with('status', 'other-browser-sessions-logged-out');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme_preference' => ['required', 'string', 'in:light,dark'],
        ]);

        $request->user()->forceFill([
            'theme_preference' => $data['theme_preference'],
        ])->save();

        return back();
    }

    /**
     * @return array<int, array{id: string, ip_address: ?string, user_agent: string, last_active: ?string, is_current_device: bool}>
     */
    protected function browserSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $currentSessionId = $request->session()->getId();

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function (object $session) use ($currentSessionId): array {
                return [
                    'id' => (string) $session->id,
                    'ip_address' => $session->ip_address ? (string) $session->ip_address : null,
                    'user_agent' => $session->user_agent ? (string) $session->user_agent : 'Onbekend apparaat',
                    'last_active' => isset($session->last_activity)
                        ? Carbon::createFromTimestamp((int) $session->last_activity)->toIso8601String()
                        : null,
                    'is_current_device' => (string) $session->id === $currentSessionId,
                ];
            })
            ->values()
            ->all();
    }
}
