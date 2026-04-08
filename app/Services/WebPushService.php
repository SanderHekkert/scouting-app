<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function sendToSubscriptions(iterable $subscriptions, string $title, string $body, ?string $url = null): array
    {
        $publicKey = (string) config('services.webpush.vapid_public_key');
        $privateKey = (string) config('services.webpush.vapid_private_key');
        $subject = (string) config('services.webpush.vapid_subject');

        if ($publicKey === '' || $privateKey === '' || $subject === '') {
            return ['success' => 0, 'failed' => 0, 'errors' => ['WebPush VAPID keys ontbreken in .env']];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?: '/dashboard',
        ], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $subscription) {
            if (! $subscription instanceof PushSubscription) {
                continue;
            }
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                ]),
                $payload
            );
        }

        $success = 0;
        $failed = 0;
        $errors = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = (string) $report->getRequest()->getUri();
            if ($report->isSuccess()) {
                $success++;

                continue;
            }

            $failed++;
            $errors[] = "{$endpoint}: {$report->getReason()}";
            PushSubscription::query()->where('endpoint', $endpoint)->delete();
        }

        return compact('success', 'failed', 'errors');
    }
}
