<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPushNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/PushNotifications', [
            'canCreate' => $this->canManagePushNotifications($request),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->canManagePushNotifications($request), 403);

        return Inertia::render('Admin/PushNotificationsShow');
    }

    public function store(Request $request, WebPushService $webPushService): RedirectResponse
    {
        abort_unless($this->canManagePushNotifications($request), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:280'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:/^\/(?!\/).*/'],
        ]);

        $subscriptions = PushSubscription::query()->get();
        $result = $webPushService->sendToSubscriptions(
            $subscriptions,
            $data['title'],
            $data['body'],
            $data['url'] ?? '/dashboard'
        );

        $message = "Push verzonden: {$result['success']} gelukt";
        if ($result['failed'] > 0) {
            $message .= ", {$result['failed']} mislukt";
        }

        return to_route('admin.push-notifications.index')->with('status', $message);
    }

    private function canManagePushNotifications(Request $request): bool
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->isGlobalAdmin() || $user->isGlobalBoardMember();
    }
}
