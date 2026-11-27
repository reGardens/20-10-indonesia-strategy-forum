<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\ContentEncoding;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\PushSubscription;

class IsfPushNotificationService
{
    public function __construct(
        protected WebPush $webPush
    ) {
    }

    /**
     * Get configured VAPID public key.
     */
    public function getVapidPublicKey(): ?string
    {
        return config('webpush.vapid.public_key');
    }

    /**
     * Store or update a push subscription from browser payload.
     */
    public function subscribe(array $data, ?User $user = null): PushSubscription
    {
        $endpoint = $data['endpoint'];
        $publicKey = $data['keys']['p256dh'] ?? ($data['public_key'] ?? null);
        $authToken = $data['keys']['auth'] ?? ($data['auth_token'] ?? null);
        $contentEncoding = $data['content_encoding'] ?? 'aes128gcm';

        /** @var PushSubscription $subscription */
        $subscription = PushSubscription::firstOrNew(['endpoint' => $endpoint]);
        $subscription->public_key = $publicKey;
        $subscription->auth_token = $authToken;
        $subscription->content_encoding = $contentEncoding;

        if ($user) {
            $subscription->subscribable_id = $user->id;
            $subscription->subscribable_type = get_class($user);
        } elseif (!$subscription->exists) {
            $subscription->subscribable_id = null;
            $subscription->subscribable_type = null;
        }

        $subscription->save();

        return $subscription;
    }

    /**
     * Remove subscription by endpoint.
     */
    public function unsubscribe(string $endpoint): bool
    {
        return (bool) PushSubscription::where('endpoint', $endpoint)->delete();
    }

    /**
     * Broadcast notification to all active push subscriptions.
     *
     * @return array{total: int, sent: int, failed: int}
     */
    public function broadcast(
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $badge = null,
        array $data = []
    ): array {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'errors' => []];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?? asset('icons/icon-192x192.png'),
            'badge' => $badge ?? asset('icons/badge-72x72.png'),
            'data' => array_merge([
                'url' => $url ?? url('/'),
                'timestamp' => now()->toIso8601String(),
            ], $data),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscriptions as $sub) {
            try {
                $encoding = $sub->content_encoding instanceof ContentEncoding
                    ? $sub->content_encoding->value
                    : ($sub->content_encoding ?? 'aes128gcm');

                $webPushSub = new Subscription(
                    $sub->endpoint,
                    $sub->public_key,
                    $sub->auth_token,
                    $encoding
                );

                $report = $this->webPush->sendOneNotification($webPushSub, $payload);

                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed++;
                    $reason = $report->getReason() ?: 'Unknown WebPush error';
                    $errors[] = $reason;
                    if ($report->isSubscriptionExpired()) {
                        PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                    }
                    Log::warning('WebPush failed for endpoint: ' . $reason, [
                        'endpoint' => $report->getEndpoint(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
                Log::warning('WebPush sendOneNotification error: ' . $e->getMessage(), [
                    'endpoint' => $sub->endpoint,
                ]);
                if (
                    str_contains($e->getMessage(), 'Invalid data provided')
                    || str_contains($e->getMessage(), 'uncompressed key')
                    || str_contains($e->getMessage(), 'Bad Request')
                ) {
                    PushSubscription::where('endpoint', $sub->endpoint)->delete();
                }
            }
        }

        return [
            'total' => $subscriptions->count(),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Send push notification directly to a single user.
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null, array $data = []): array
    {
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'errors' => []];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => asset('icons/icon-192x192.png'),
            'badge' => asset('icons/badge-72x72.png'),
            'data' => array_merge([
                'url' => $url ?? url('/'),
                'timestamp' => now()->toIso8601String(),
            ], $data),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscriptions as $sub) {
            try {
                $encoding = $sub->content_encoding instanceof ContentEncoding
                    ? $sub->content_encoding->value
                    : ($sub->content_encoding ?? 'aes128gcm');

                $webPushSub = new Subscription(
                    $sub->endpoint,
                    $sub->public_key,
                    $sub->auth_token,
                    $encoding
                );

                $report = $this->webPush->sendOneNotification($webPushSub, $payload);

                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed++;
                    $reason = $report->getReason() ?: 'Unknown WebPush error';
                    $errors[] = $reason;
                    if ($report->isSubscriptionExpired()) {
                        PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                    }
                    Log::warning('WebPush failed for user ' . $user->id . ': ' . $reason);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
                Log::warning('WebPush sendOneNotification error for user ' . $user->id . ': ' . $e->getMessage());
                if (
                    str_contains($e->getMessage(), 'Invalid data provided')
                    || str_contains($e->getMessage(), 'uncompressed key')
                ) {
                    PushSubscription::where('endpoint', $sub->endpoint)->delete();
                }
            }
        }

        return [
            'total' => $subscriptions->count(),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Send push notification to all users matching any of the specified roles.
     *
     * @param array<string> $roles
     * @return array{total: int, sent: int, failed: int}
     */
    public function sendToRoles(array $roles, string $title, string $body, ?string $url = null, array $data = []): array
    {
        try {
            $users = User::role($roles)->with('pushSubscriptions')->get();
            $total = 0;
            $sent = 0;
            $failed = 0;

            foreach ($users as $user) {
                if ($user->pushSubscriptions && $user->pushSubscriptions->isNotEmpty()) {
                    $res = $this->sendToUser($user, $title, $body, $url, $data);
                    $total += $res['total'];
                    $sent += $res['sent'];
                    $failed += $res['failed'];
                }
            }

            return ['total' => $total, 'sent' => $sent, 'failed' => $failed];
        } catch (\Throwable $e) {
            Log::warning('sendToRoles failed: ' . $e->getMessage());
            return ['total' => 0, 'sent' => 0, 'failed' => 0];
        }
    }
}
