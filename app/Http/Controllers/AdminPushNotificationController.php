<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPushNotificationController extends Controller
{
    public function store(Request $request, WebPushService $webPushService): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:280'],
            'url' => ['nullable', 'url', 'max:2048'],
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

        return back()->with('status', $message);
    }
}
